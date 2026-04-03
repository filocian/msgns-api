<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use Src\Products\Application\Commands\CompleteConfiguration\CompleteConfigurationCommand;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class CompleteConfigurationController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    public function __invoke(int $id): JsonResponse
    {
        $product = $this->commandBus->dispatch(new CompleteConfigurationCommand(productId: $id));

        return ApiResponseFactory::ok(['product' => $product]);
    }
}
