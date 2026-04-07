<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use OpenApi\Attributes as OA;
use Src\Identity\Application\Queries\ExportUsers\ExportUsersQuery;
use Src\Identity\Application\Resources\AdminUserExportResource;
use Src\Identity\Infrastructure\Http\Requests\ExportUsersRequest;
use Src\Shared\Core\Bus\QueryBus;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AdminExportUsersController extends Controller
{
    public function __construct(
        private readonly QueryBus $queryBus,
    ) {}

    #[OA\Get(
        path: '/identity/admin/users/export',
        summary: 'Export users as CSV',
        description: 'Generates a CSV file with user data matching the provided filters.',
        operationId: 'exportUsers',
        tags: ['Identity - Admin'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'active', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'role', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'created_from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'created_to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'CSV file download',
                content: new OA\MediaType(
                    mediaType: 'text/csv',
                    schema: new OA\Schema(type: 'string', format: 'binary')
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function __invoke(ExportUsersRequest $request): StreamedResponse
    {
        set_time_limit(0);

        /** @var iterable<int, \App\Models\User> $users */
        $users = $this->queryBus->dispatch(new ExportUsersQuery(
            search: $request->validated('search'),
            active: $request->has('active') ? (bool) $request->validated('active') : null,
            role: $request->validated('role'),
            createdFrom: $request->validated('created_from'),
            createdTo: $request->validated('created_to'),
        ));

        $filename = 'users-export-' . date('Y-m-d') . '.csv';

        $response = new StreamedResponse(function () use ($users): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            // UTF-8 BOM for Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            // Header row
            fputcsv($handle, AdminUserExportResource::csvHeaders());

            $rowCount = 0;

            foreach ($users as $user) {
                /** @var ?string $phone */
                $phone = $user->getAttribute('phone');
                /** @var ?string $country */
                $country = $user->getAttribute('country');
                /** @var ?string $defaultLocale */
                $defaultLocale = $user->getAttribute('default_locale');

                $resource = new AdminUserExportResource(
                    id: $user->id,
                    name: $user->name,
                    email: $user->email,
                    phone: $phone,
                    country: $country,
                    defaultLocale: $defaultLocale,
                    active: $user->active ? 'yes' : 'no',
                    emailVerified: $user->email_verified_at !== null ? 'yes' : 'no',
                    roles: $user->roles->pluck('name')->sort()->implode(','),
                    createdAt: $user->created_at?->toIso8601String() ?? '',
                    updatedAt: $user->updated_at?->toIso8601String() ?? '',
                );

                fputcsv($handle, $resource->toCsvRow());

                $rowCount++;

                if ($rowCount % 500 === 0) {
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'no-store, no-cache');
        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
    }
}
