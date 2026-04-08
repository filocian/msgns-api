<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\AddWhatsappPhone;

use Src\Products\Application\Resources\WhatsappPhoneResource;
use Src\Products\Domain\Entities\WhatsappPhone;
use Src\Products\Domain\Events\WhatsappPhoneAdded;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Ports\WhatsappPhoneRepositoryPort;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Ports\TransactionPort;

final class AddWhatsappPhoneHandler implements CommandHandler
{
    public function __construct(
        private readonly ProductRepositoryPort $productRepository,
        private readonly WhatsappPhoneRepositoryPort $phoneRepository,
        private readonly TransactionPort $transaction,
    ) {}

    public function handle(Command $command): WhatsappPhoneResource
    {
        assert($command instanceof AddWhatsappPhoneCommand);

        return $this->transaction->run(function () use ($command): WhatsappPhoneResource {
            $product = $this->productRepository->findById($command->productId);

            if ($product === null) {
                throw NotFound::entity('product', (string) $command->productId);
            }

            $phone = $this->phoneRepository->save(WhatsappPhone::create(
                productId: $product->id,
                phone: $command->phone,
                prefix: $command->prefix,
            ));

            $product->recordEvent(new WhatsappPhoneAdded(
                productId: $product->id,
                phoneId: $phone->id,
            ));

            $this->productRepository->save($product);

            return WhatsappPhoneResource::fromEntity($phone);
        });
    }
}
