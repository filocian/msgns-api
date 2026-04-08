<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\RemoveWhatsappPhone;

use Src\Products\Domain\Errors\WhatsappConfigurationError;
use Src\Products\Domain\Events\WhatsappPhoneRemoved;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Ports\WhatsappPhoneRepositoryPort;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Ports\TransactionPort;

final class RemoveWhatsappPhoneHandler implements CommandHandler
{
    public function __construct(
        private readonly ProductRepositoryPort $productRepository,
        private readonly WhatsappPhoneRepositoryPort $phoneRepository,
        private readonly TransactionPort $transaction,
    ) {}

    public function handle(Command $command): mixed
    {
        assert($command instanceof RemoveWhatsappPhoneCommand);

        $this->transaction->run(function () use ($command): void {
            $product = $this->productRepository->findById($command->productId);

            if ($product === null) {
                throw NotFound::entity('product', (string) $command->productId);
            }

            $phone = $this->phoneRepository->findById($command->phoneId);

            if ($phone === null || $phone->productId !== $product->id) {
                throw NotFound::entity('whatsapp_phone', (string) $command->phoneId);
            }

            $phoneCount = $this->phoneRepository->countByProductId($product->id);

            if ($phoneCount <= 1) {
                throw WhatsappConfigurationError::lastPhone($product->id);
            }

            $this->phoneRepository->delete($phone->id);

            $product->recordEvent(new WhatsappPhoneRemoved(
                productId: $product->id,
                phoneId: $phone->id,
            ));

            $this->productRepository->save($product);
        });

        return null;
    }
}
