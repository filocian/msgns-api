<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Src\Products\Application\Commands\AddWhatsappMessage\AddWhatsappMessageCommand;
use Src\Products\Application\Commands\AddWhatsappMessage\AddWhatsappMessageHandler;
use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Entities\WhatsappLocale;
use Src\Products\Domain\Entities\WhatsappMessage;
use Src\Products\Domain\Entities\WhatsappPhone;
use Src\Products\Domain\Errors\WhatsappConfigurationError;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Ports\WhatsappLocaleRepositoryPort;
use Src\Products\Domain\Ports\WhatsappMessageRepositoryPort;
use Src\Products\Domain\Ports\WhatsappPhoneRepositoryPort;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Shared\Core\Errors\ValidationFailed;
use Src\Shared\Core\Ports\TransactionPort;

function makeWhatsappProductForAddMessage(int $id = 1): Product
{
    return Product::fromPersistence(
        id: $id,
        productTypeId: 1,
        userId: 7,
        model: 'whatsapp',
        linkedToProductId: null,
        password: 'secret',
        targetUrl: null,
        usage: 0,
        name: 'WhatsApp Product',
        description: null,
        active: true,
        configurationStatus: ConfigurationStatus::from(ConfigurationStatus::COMPLETED),
        assignedAt: null,
        size: null,
        createdAt: new DateTimeImmutable('2024-01-01T00:00:00+00:00'),
        updatedAt: new DateTimeImmutable('2024-01-01T00:00:00+00:00'),
        deletedAt: null,
    );
}

describe('AddWhatsappMessageHandler', function () {
    it('rejects duplicate locale per phone', function () {
        $product = makeWhatsappProductForAddMessage();
        $phone = WhatsappPhone::fromPersistence(
            id: 10, productId: 1, phone: '612345678', prefix: '34',
            createdAt: new DateTimeImmutable(), updatedAt: new DateTimeImmutable(),
        );
        $locale = WhatsappLocale::fromPersistence(id: 5, code: 'es_ES');

        /** @var MockInterface&ProductRepositoryPort $productRepo */
        $productRepo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&WhatsappPhoneRepositoryPort $phoneRepo */
        $phoneRepo = Mockery::mock(WhatsappPhoneRepositoryPort::class);
        /** @var MockInterface&WhatsappMessageRepositoryPort $messageRepo */
        $messageRepo = Mockery::mock(WhatsappMessageRepositoryPort::class);
        /** @var MockInterface&WhatsappLocaleRepositoryPort $localeRepo */
        $localeRepo = Mockery::mock(WhatsappLocaleRepositoryPort::class);
        /** @var MockInterface&TransactionPort $transaction */
        $transaction = Mockery::mock(TransactionPort::class);
        $transaction->shouldReceive('run')->once()->andReturnUsing(static fn (callable $cb): mixed => $cb());

        $productRepo->shouldReceive('findById')->once()->with(1)->andReturn($product);
        $phoneRepo->shouldReceive('findById')->once()->with(10)->andReturn($phone);
        $localeRepo->shouldReceive('findByCode')->once()->with('es_ES')->andReturn($locale);
        $messageRepo->shouldReceive('existsByPhoneIdAndLocaleId')->once()->with(10, 5)->andReturn(true);
        $messageRepo->shouldNotReceive('save');

        $handler = new AddWhatsappMessageHandler($productRepo, $phoneRepo, $messageRepo, $localeRepo, $transaction);

        expect(fn () => $handler->handle(new AddWhatsappMessageCommand(
            productId: 1, phoneId: 10, localeCode: 'es_ES', message: 'Hola',
        )))->toThrow(WhatsappConfigurationError::class);
    });

    it('rejects invalid locale code', function () {
        $product = makeWhatsappProductForAddMessage();
        $phone = WhatsappPhone::fromPersistence(
            id: 10, productId: 1, phone: '612345678', prefix: '34',
            createdAt: new DateTimeImmutable(), updatedAt: new DateTimeImmutable(),
        );

        /** @var MockInterface&ProductRepositoryPort $productRepo */
        $productRepo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&WhatsappPhoneRepositoryPort $phoneRepo */
        $phoneRepo = Mockery::mock(WhatsappPhoneRepositoryPort::class);
        /** @var MockInterface&WhatsappMessageRepositoryPort $messageRepo */
        $messageRepo = Mockery::mock(WhatsappMessageRepositoryPort::class);
        /** @var MockInterface&WhatsappLocaleRepositoryPort $localeRepo */
        $localeRepo = Mockery::mock(WhatsappLocaleRepositoryPort::class);
        /** @var MockInterface&TransactionPort $transaction */
        $transaction = Mockery::mock(TransactionPort::class);
        $transaction->shouldReceive('run')->once()->andReturnUsing(static fn (callable $cb): mixed => $cb());

        $productRepo->shouldReceive('findById')->once()->with(1)->andReturn($product);
        $phoneRepo->shouldReceive('findById')->once()->with(10)->andReturn($phone);
        $localeRepo->shouldReceive('findByCode')->once()->with('zz_ZZ')->andReturn(null);

        $handler = new AddWhatsappMessageHandler($productRepo, $phoneRepo, $messageRepo, $localeRepo, $transaction);

        expect(fn () => $handler->handle(new AddWhatsappMessageCommand(
            productId: 1, phoneId: 10, localeCode: 'zz_ZZ', message: 'Hola',
        )))->toThrow(ValidationFailed::class);
    });

    it('saves message when locale is unique per phone', function () {
        $product = makeWhatsappProductForAddMessage();
        $phone = WhatsappPhone::fromPersistence(
            id: 10, productId: 1, phone: '612345678', prefix: '34',
            createdAt: new DateTimeImmutable(), updatedAt: new DateTimeImmutable(),
        );
        $locale = WhatsappLocale::fromPersistence(id: 5, code: 'es_ES');
        $savedMessage = WhatsappMessage::fromPersistence(
            id: 100, productId: 1, phoneId: 10, localeId: 5, localeCode: 'es_ES',
            message: 'Hola', isDefault: false, phone: '612345678', prefix: '34',
            createdAt: new DateTimeImmutable(), updatedAt: new DateTimeImmutable(),
        );

        /** @var MockInterface&ProductRepositoryPort $productRepo */
        $productRepo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&WhatsappPhoneRepositoryPort $phoneRepo */
        $phoneRepo = Mockery::mock(WhatsappPhoneRepositoryPort::class);
        /** @var MockInterface&WhatsappMessageRepositoryPort $messageRepo */
        $messageRepo = Mockery::mock(WhatsappMessageRepositoryPort::class);
        /** @var MockInterface&WhatsappLocaleRepositoryPort $localeRepo */
        $localeRepo = Mockery::mock(WhatsappLocaleRepositoryPort::class);
        /** @var MockInterface&TransactionPort $transaction */
        $transaction = Mockery::mock(TransactionPort::class);
        $transaction->shouldReceive('run')->once()->andReturnUsing(static fn (callable $cb): mixed => $cb());

        $productRepo->shouldReceive('findById')->once()->with(1)->andReturn($product);
        $productRepo->shouldReceive('save')->once()->andReturnUsing(static fn (Product $p): Product => $p);
        $phoneRepo->shouldReceive('findById')->once()->with(10)->andReturn($phone);
        $localeRepo->shouldReceive('findByCode')->once()->with('es_ES')->andReturn($locale);
        $messageRepo->shouldReceive('existsByPhoneIdAndLocaleId')->once()->with(10, 5)->andReturn(false);
        $messageRepo->shouldReceive('save')->once()->andReturn($savedMessage);

        $handler = new AddWhatsappMessageHandler($productRepo, $phoneRepo, $messageRepo, $localeRepo, $transaction);
        $result = $handler->handle(new AddWhatsappMessageCommand(
            productId: 1, phoneId: 10, localeCode: 'es_ES', message: 'Hola',
        ));

        expect($result->id)->toBe(100)
            ->and($result->localeCode)->toBe('es_ES')
            ->and($result->message)->toBe('Hola')
            ->and($result->isDefault)->toBeFalse();
    });
});
