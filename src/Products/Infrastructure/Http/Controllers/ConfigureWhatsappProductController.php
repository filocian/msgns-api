<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Src\Products\Application\Commands\ConfigureWhatsappProduct\ConfigureWhatsappProductCommand;
use Src\Products\Infrastructure\Http\Requests\ConfigureWhatsappProductRequest;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class ConfigureWhatsappProductController
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    public function __invoke(ConfigureWhatsappProductRequest $request, int $id): JsonResponse
    {
        $product = $this->commandBus->dispatch(new ConfigureWhatsappProductCommand(
            productId: $id,
            phone: (string) $request->validated('phone'),
            prefix: (string) $request->validated('prefix'),
            message: (string) $request->validated('message'),
            localeCode: (string) $request->validated('locale_code'),
        ));

        return ApiResponseFactory::ok(['product' => $product]);
    }
}
