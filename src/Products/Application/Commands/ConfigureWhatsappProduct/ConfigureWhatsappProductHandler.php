<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\ConfigureWhatsappProduct;

use DateTimeImmutable;
use Src\Products\Application\Resources\ProductResource;
use Src\Products\Domain\Entities\WhatsappMessage;
use Src\Products\Domain\Entities\WhatsappPhone;
use Src\Products\Domain\Events\ProductConfigurationCompleted;
use Src\Products\Domain\Events\WhatsappMessageAdded;
use Src\Products\Domain\Events\WhatsappPhoneAdded;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Ports\WhatsappLocaleRepositoryPort;
use Src\Products\Domain\Ports\WhatsappMessageRepositoryPort;
use Src\Products\Domain\Ports\WhatsappPhoneRepositoryPort;
use Src\Products\Domain\Services\ConfigurationFlowResolver;
use Src\Products\Domain\Services\ProductConfigStatusService;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Errors\ValidationFailed;
use Src\Shared\Core\Ports\TransactionPort;

final class ConfigureWhatsappProductHandler implements CommandHandler
{
    public function __construct(
        private readonly ProductRepositoryPort $productRepository,
        private readonly WhatsappPhoneRepositoryPort $phoneRepository,
        private readonly WhatsappMessageRepositoryPort $messageRepository,
        private readonly WhatsappLocaleRepositoryPort $localeRepository,
        private readonly ProductConfigStatusService $configStatusService,
        private readonly ConfigurationFlowResolver $flowResolver,
        private readonly TransactionPort $transaction,
    ) {}

    public function handle(Command $command): ProductResource
    {
        assert($command instanceof ConfigureWhatsappProductCommand);

        return $this->transaction->run(function () use ($command): ProductResource {
            $product = $this->productRepository->findById($command->productId);

            if ($product === null) {
                throw NotFound::entity('product', (string) $command->productId);
            }

            $locale = $this->localeRepository->findByCode($command->localeCode);

            if ($locale === null) {
                throw ValidationFailed::because('invalid_locale_code', [
                    'locale_code' => $command->localeCode,
                ]);
            }

            $phone = $this->phoneRepository->save(WhatsappPhone::create(
                productId: $product->id,
                phone: $command->phone,
                prefix: $command->prefix,
            ));

            $message = $this->messageRepository->save(WhatsappMessage::create(
                productId: $product->id,
                phoneId: $phone->id,
                localeId: $locale->id,
                localeCode: $locale->code,
                message: $command->message,
                isDefault: true,
            ));

            $product->recordEvent(new WhatsappPhoneAdded(
                productId: $product->id,
                phoneId: $phone->id,
            ));
            $product->recordEvent(new WhatsappMessageAdded(
                productId: $product->id,
                messageId: $message->id,
            ));

            // Advance configuration status (skip business-set for whatsapp)
            $nextState = $this->flowResolver->nextState($product->model->value, $product->configurationStatus);

            if ($nextState !== null && $product->configurationStatus->canTransitionTo($nextState)) {
                $this->configStatusService->transition($product, $nextState->value);
            }

            $completionState = $this->flowResolver->nextState($product->model->value, $product->configurationStatus);

            if ($completionState?->value === ConfigurationStatus::COMPLETED
                && $product->configurationStatus->canTransitionTo($completionState)) {
                $this->configStatusService->transition($product, $completionState->value);
                $product->recordEvent(new ProductConfigurationCompleted(
                    productId: $product->id,
                    model: $product->model->value,
                    completedAt: new DateTimeImmutable(),
                ));
            }

            return ProductResource::fromEntity($this->productRepository->save($product));
        });
    }
}
