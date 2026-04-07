<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;
use Src\Identity\Application\Commands\UpdateMyProfile\UpdateMyProfileCommand;
use Src\Identity\Infrastructure\Http\Requests\UpdateMyProfileRequest;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class UpdateMyProfileController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Patch(
        path: '/identity/me',
        summary: 'Update current user profile',
        description: 'Updates the authenticated user\'s profile information.',
        operationId: 'updateMyProfile',
        tags: ['Identity'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'phone', type: 'string', nullable: true),
                    new OA\Property(property: 'country', type: 'string', nullable: true),
                    new OA\Property(property: 'default_locale', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Profile updated', content: new OA\JsonContent(ref: '#/components/schemas/UserResource')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function __invoke(UpdateMyProfileRequest $request): JsonResponse
    {
        $userId = (int) Auth::id();
        $user = $this->commandBus->dispatch(new UpdateMyProfileCommand(
            userId: $userId,
            name: $request->input('name'),
            phone: $request->input('phone'),
            country: $request->input('country'),
            defaultLocale: $request->input('default_locale'),
        ));
        return ApiResponseFactory::ok($user);
    }
}
