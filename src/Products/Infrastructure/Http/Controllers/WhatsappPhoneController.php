<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Src\Products\Application\Commands\AddWhatsappPhone\AddWhatsappPhoneCommand;
use Src\Products\Application\Commands\RemoveWhatsappPhone\RemoveWhatsappPhoneCommand;
use Src\Products\Infrastructure\Http\Requests\AddWhatsappPhoneRequest;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class WhatsappPhoneController
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    public function store(AddWhatsappPhoneRequest $request, int $id): JsonResponse
    {
        $phone = $this->commandBus->dispatch(new AddWhatsappPhoneCommand(
            productId: $id,
            phone: (string) $request->validated('phone'),
            prefix: (string) $request->validated('prefix'),
        ));

        return ApiResponseFactory::created(['phone' => $phone]);
    }

    public function destroy(int $id, int $phoneId): Response
    {
        $this->commandBus->dispatch(new RemoveWhatsappPhoneCommand(
            productId: $id,
            phoneId: $phoneId,
        ));

        return ApiResponseFactory::noContent();
    }
}
