<?php

declare(strict_types=1);

namespace App\Http\Controllers\Identity;

use App\Http\Contracts\Controller;
use App\Http\Requests\Identity\AdminSetPasswordRequest;
use App\Http\Requests\Identity\AdminUpdateUserRequest;
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
use Src\Identity\Application\Queries\GetUser\GetUserQuery;
use Src\Identity\Application\Queries\ListUsers\ListUsersQuery;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Core\Bus\QueryBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

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
