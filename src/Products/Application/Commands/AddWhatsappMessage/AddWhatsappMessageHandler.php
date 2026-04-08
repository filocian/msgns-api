<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\AddWhatsappMessage;

use Src\Products\Application\Resources\WhatsappMessageResource;
use Src\Products\Domain\Entities\WhatsappMessage;
use Src\Products\Domain\Errors\WhatsappConfigurationError;
use Src\Products\Domain\Events\WhatsappMessageAdded;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Ports\WhatsappLocaleRepositoryPort;
use Src\Products\Domain\Ports\WhatsappMessageRepositoryPort;
use Src\Products\Domain\Ports\WhatsappPhoneRepositoryPort;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Errors\ValidationFailed;
use Src\Shared\Core\Ports\TransactionPort;

final class AddWhatsappMessageHandler implements CommandHandler
{
    public function __construct(
        private readonly ProductRepositoryPort $productRepository,
        private readonly WhatsappPhoneRepositoryPort $phoneRepository,
        private readonly WhatsappMessageRepositoryPort $messageRepository,
        private readonly WhatsappLocaleRepositoryPort $localeRepository,
        private readonly TransactionPort $transaction,
    ) {}

    public function handle(Command $command): WhatsappMessageResource
    {
        assert($command instanceof AddWhatsappMessageCommand);

        return $this->transaction->run(function () use ($command): WhatsappMessageResource {
            $product = $this->productRepository->findById($command->productId);

            if ($product === null) {
                throw NotFound::entity('product', (string) $command->productId);
            }

            $phone = $this->phoneRepository->findById($command->phoneId);

            if ($phone === null || $phone->productId !== $product->id) {
                throw NotFound::entity('whatsapp_phone', (string) $command->phoneId);
            }

            $locale = $this->localeRepository->findByCode($command->localeCode);

            if ($locale === null) {
                throw ValidationFailed::because('invalid_locale_code', [
                    'locale_code' => $command->localeCode,
                ]);
            }

            if ($this->messageRepository->existsByPhoneIdAndLocaleId($phone->id, $locale->id)) {
                throw WhatsappConfigurationError::duplicateLocale($phone->id, $locale->code);
            }

            $message = $this->messageRepository->save(WhatsappMessage::create(
                productId: $product->id,
                phoneId: $phone->id,
                localeId: $locale->id,
                localeCode: $locale->code,
                message: $command->message,
                isDefault: false,
            ));

            $product->recordEvent(new WhatsappMessageAdded(
                productId: $product->id,
                messageId: $message->id,
            ));

            $this->productRepository->save($product);

            return WhatsappMessageResource::fromEntity($message);
        });
    }
}
