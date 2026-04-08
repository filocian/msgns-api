<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\SetDefaultWhatsappMessage;

use Src\Products\Application\Resources\WhatsappMessageResource;
use Src\Products\Domain\Events\WhatsappDefaultMessageChanged;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Ports\WhatsappMessageRepositoryPort;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Ports\TransactionPort;

final class SetDefaultWhatsappMessageHandler implements CommandHandler
{
    public function __construct(
        private readonly ProductRepositoryPort $productRepository,
        private readonly WhatsappMessageRepositoryPort $messageRepository,
        private readonly TransactionPort $transaction,
    ) {}

    public function handle(Command $command): WhatsappMessageResource
    {
        assert($command instanceof SetDefaultWhatsappMessageCommand);

        return $this->transaction->run(function () use ($command): WhatsappMessageResource {
            $product = $this->productRepository->findById($command->productId);

            if ($product === null) {
                throw NotFound::entity('product', (string) $command->productId);
            }

            $message = $this->messageRepository->findById($command->messageId);

            if ($message === null || $message->productId !== $product->id) {
                throw NotFound::entity('whatsapp_message', (string) $command->messageId);
            }

            // Product-level scope: clear ALL defaults for this product, then set the new one
            $this->messageRepository->clearDefaultsForProduct($product->id);

            $message->isDefault = true;
            $savedMessage = $this->messageRepository->save($message);

            $product->recordEvent(new WhatsappDefaultMessageChanged(
                productId: $product->id,
                messageId: $savedMessage->id,
            ));

            $this->productRepository->save($product);

            return WhatsappMessageResource::fromEntity($savedMessage);
        });
    }
}
