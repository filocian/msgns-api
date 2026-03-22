<?php

declare(strict_types=1);

namespace App\Http\Controllers\Identity;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use Src\Identity\Application\Queries\ListPermissions\ListPermissionsQuery;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Core\Bus\QueryBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class AdminPermissionController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
    ) {}

    public function index(): JsonResponse
    {
        $permissions = $this->queryBus->dispatch(new ListPermissionsQuery());
        return ApiResponseFactory::ok($permissions);
    }
}
