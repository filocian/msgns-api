<?php

declare(strict_types=1);

namespace App\Http\Controllers\Identity;

use App\Http\Contracts\Controller;
use App\Http\Requests\Identity\AdminSetPasswordRequest;
use App\Http\Requests\Identity\AdminUpdateUserRequest;
use App\Http\Requests\Identity\ExportUsersRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Src\Identity\Application\Commands\AdminActivateUser\AdminActivateUserCommand;
use Src\Identity\Application\Commands\AdminDeactivateUser\AdminDeactivateUserCommand;
use Src\Identity\Application\Commands\AdminSetEmailVerified\AdminSetEmailVerifiedCommand;
use Src\Identity\Application\Commands\AdminSetPassword\AdminSetPasswordCommand;
use Src\Identity\Application\Commands\AdminUpdateUser\AdminUpdateUserCommand;
use Src\Identity\Application\Queries\ExportUsers\ExportUsersQuery;
use Src\Identity\Application\Queries\GetUser\GetUserQuery;
use Src\Identity\Application\Queries\ListUsers\ListUsersQuery;
use Src\Identity\Application\Resources\AdminUserExportResource;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Core\Bus\QueryBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AdminUserController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->queryBus->dispatch(new ListUsersQuery(
            page: (int) $request->input('page', 1),
            perPage: (int) $request->input('per_page', 15),
            sortBy: $request->input('sort_by', 'created_at'),
            sortDir: $request->input('sort_dir', 'desc'),
            search: $request->input('search'),
            active: $request->has('active') ? (bool) $request->input('active') : null,
            role: $request->input('role'),
        ));
        return ApiResponseFactory::paginated($result);
    }

    public function export(ExportUsersRequest $request): StreamedResponse
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

    public function show(int $id): JsonResponse
    {
        $user = $this->queryBus->dispatch(new GetUserQuery(userId: $id));
        return ApiResponseFactory::ok($user);
    }

    public function update(AdminUpdateUserRequest $request, int $id): JsonResponse
    {
        $user = $this->commandBus->dispatch(new AdminUpdateUserCommand(
            userId: $id,
            name: $request->input('name'),
            email: $request->input('email'),
            phone: $request->input('phone'),
            country: $request->input('country'),
            defaultLocale: $request->input('default_locale'),
        ));
        return ApiResponseFactory::ok($user);
    }

    public function setPassword(AdminSetPasswordRequest $request, int $id): Response
    {
        $this->commandBus->dispatch(new AdminSetPasswordCommand(
            userId: $id,
            hashedPassword: Hash::make($request->input('password')),
        ));
        return ApiResponseFactory::noContent();
    }

    public function setEmailVerified(int $id): JsonResponse
    {
        $user = $this->commandBus->dispatch(new AdminSetEmailVerifiedCommand(
            userId: $id,
        ));
        return ApiResponseFactory::ok($user);
    }

    public function deactivate(int $id): JsonResponse
    {
        $user = $this->commandBus->dispatch(new AdminDeactivateUserCommand(
            userId: $id,
            deactivatedBy: Auth::id(),
        ));
        return ApiResponseFactory::ok($user);
    }

    public function activate(int $id): JsonResponse
    {
        $user = $this->commandBus->dispatch(new AdminActivateUserCommand(
            userId: $id,
            activatedBy: Auth::id(),
        ));
        return ApiResponseFactory::ok($user);
    }
}
