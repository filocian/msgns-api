<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Src\Products\Application\Commands\AddWhatsappMessage\AddWhatsappMessageCommand;
use Src\Products\Application\Commands\RemoveWhatsappMessage\RemoveWhatsappMessageCommand;
use Src\Products\Application\Commands\SetDefaultWhatsappMessage\SetDefaultWhatsappMessageCommand;
use Src\Products\Infrastructure\Http\Requests\AddWhatsappMessageRequest;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class WhatsappMessageController
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    public function store(AddWhatsappMessageRequest $request, int $id): JsonResponse
    {
        $message = $this->commandBus->dispatch(new AddWhatsappMessageCommand(
            productId: $id,
            phoneId: (int) $request->validated('phone_id'),
            localeCode: (string) $request->validated('locale_code'),
            message: (string) $request->validated('message'),
        ));

        return ApiResponseFactory::created(['message' => $message]);
    }

    public function destroy(int $id, int $messageId): Response
    {
        $this->commandBus->dispatch(new RemoveWhatsappMessageCommand(
            productId: $id,
            messageId: $messageId,
        ));

        return ApiResponseFactory::noContent();
    }

    public function setDefault(int $id, int $messageId): JsonResponse
    {
        $message = $this->commandBus->dispatch(new SetDefaultWhatsappMessageCommand(
            productId: $id,
            messageId: $messageId,
        ));

        return ApiResponseFactory::ok(['message' => $message]);
    }
}
