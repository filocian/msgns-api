<?php

declare(strict_types=1);

namespace App\Http\Controllers\Identity;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Src\Identity\Application\Commands\StartImpersonation\StartImpersonationCommand;
use Src\Identity\Application\Commands\StopImpersonation\StopImpersonationCommand;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Core\Bus\QueryBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class ImpersonationController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
    ) {}

    public function start(Request $request, int $id): JsonResponse
    {
        $adminUserId = Auth::id();
        $result = $this->commandBus->dispatch(new StartImpersonationCommand(
            adminUserId: $adminUserId,
            targetUserId: $id,
        ));
        return ApiResponseFactory::ok($result);
    }

    public function stop(Request $request): JsonResponse
    {
        $adminUserId = Auth::id();
        $result = $this->commandBus->dispatch(new StopImpersonationCommand(
            adminUserId: $adminUserId,
        ));
        return ApiResponseFactory::ok($result);
    }
}
