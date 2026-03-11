<?php

declare(strict_types=1);

namespace App\Http\Controllers\Identity;

use App\Http\Contracts\Controller;
use App\Http\Requests\Identity\AdminAssignRoleRequest;
use App\Http\Requests\Identity\AdminCreateRoleRequest;
use App\Http\Requests\Identity\AdminUpdateRoleRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Src\Identity\Application\Commands\AssignRole\AssignRoleCommand;
use Src\Identity\Application\Commands\CreateRole\CreateRoleCommand;
use Src\Identity\Application\Commands\DeleteRole\DeleteRoleCommand;
use Src\Identity\Application\Commands\RemoveRole\RemoveRoleCommand;
use Src\Identity\Application\Commands\UpdateRole\UpdateRoleCommand;
use Src\Identity\Application\Queries\ListRoles\ListRolesQuery;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Core\Bus\QueryBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class AdminRoleController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
    ) {}

    public function index(): JsonResponse
    {
        $roles = $this->queryBus->dispatch(new ListRolesQuery());
        return ApiResponseFactory::ok($roles);
    }

    public function store(AdminCreateRoleRequest $request): JsonResponse
    {
        $role = $this->commandBus->dispatch(new CreateRoleCommand(
            name: $request->input('name'),
        ));
        return ApiResponseFactory::created($role);
    }

    public function update(AdminUpdateRoleRequest $request, int $id): JsonResponse
    {
        $role = $this->commandBus->dispatch(new UpdateRoleCommand(
            id: $id,
            name: $request->input('name'),
        ));
        return ApiResponseFactory::ok($role);
    }

    public function destroy(int $id): Response
    {
        $this->commandBus->dispatch(new DeleteRoleCommand(id: $id));
        return ApiResponseFactory::noContent();
    }

    public function assignToUser(AdminAssignRoleRequest $request, int $id): Response
    {
        $this->commandBus->dispatch(new AssignRoleCommand(
            userId: $id,
            role: $request->input('role'),
        ));
        return ApiResponseFactory::noContent();
    }

    public function removeFromUser(int $id, string $role): Response
    {
        $this->commandBus->dispatch(new RemoveRoleCommand(
            userId: $id,
            role: $role,
        ));
        return ApiResponseFactory::noContent();
    }
}
