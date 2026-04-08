<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\RemoveWhatsappMessage;

use Src\Products\Domain\Errors\WhatsappConfigurationError;
use Src\Products\Domain\Events\WhatsappMessageRemoved;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Ports\WhatsappMessageRepositoryPort;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Ports\TransactionPort;

final class RemoveWhatsappMessageHandler implements CommandHandler
{
    public function __construct(
        private readonly ProductRepositoryPort $productRepository,
        private readonly WhatsappMessageRepositoryPort $messageRepository,
        private readonly TransactionPort $transaction,
    ) {}

    public function handle(Command $command): mixed
    {
        assert($command instanceof RemoveWhatsappMessageCommand);

        $this->transaction->run(function () use ($command): void {
            $product = $this->productRepository->findById($command->productId);

            if ($product === null) {
                throw NotFound::entity('product', (string) $command->productId);
            }

            $message = $this->messageRepository->findById($command->messageId);

            if ($message === null || $message->productId !== $product->id) {
                throw NotFound::entity('whatsapp_message', (string) $command->messageId);
            }

            if ($message->isDefault) {
                throw WhatsappConfigurationError::defaultMessageRemoval($message->id);
            }

            $this->messageRepository->delete($message->id);

            $product->recordEvent(new WhatsappMessageRemoved(
                productId: $product->id,
                messageId: $message->id,
            ));

            $this->productRepository->save($product);
        });

        return null;
    }
}
