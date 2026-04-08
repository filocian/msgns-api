<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Src\Products\Application\Commands\RemoveWhatsappMessage\RemoveWhatsappMessageCommand;
use Src\Products\Application\Commands\RemoveWhatsappMessage\RemoveWhatsappMessageHandler;
use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Entities\WhatsappMessage;
use Src\Products\Domain\Errors\WhatsappConfigurationError;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Ports\WhatsappMessageRepositoryPort;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Shared\Core\Ports\TransactionPort;

function makeWhatsappProductForMessageGuardrail(int $id = 1): Product
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

describe('RemoveWhatsappMessageHandler', function () {
    it('rejects removal of the default message', function () {
        $product = makeWhatsappProductForMessageGuardrail();
        $defaultMessage = WhatsappMessage::fromPersistence(
            id: 5, productId: 1, phoneId: 10, localeId: 1, localeCode: 'es_ES',
            message: 'Hola', isDefault: true, phone: '612345678', prefix: '34',
            createdAt: new DateTimeImmutable(), updatedAt: new DateTimeImmutable(),
        );

        /** @var MockInterface&ProductRepositoryPort $productRepo */
        $productRepo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&WhatsappMessageRepositoryPort $messageRepo */
        $messageRepo = Mockery::mock(WhatsappMessageRepositoryPort::class);
        /** @var MockInterface&TransactionPort $transaction */
        $transaction = Mockery::mock(TransactionPort::class);
        $transaction->shouldReceive('run')->once()->andReturnUsing(static fn (callable $cb): mixed => $cb());

        $productRepo->shouldReceive('findById')->once()->with(1)->andReturn($product);
        $messageRepo->shouldReceive('findById')->once()->with(5)->andReturn($defaultMessage);
        $messageRepo->shouldNotReceive('delete');

        $handler = new RemoveWhatsappMessageHandler($productRepo, $messageRepo, $transaction);

        expect(fn () => $handler->handle(new RemoveWhatsappMessageCommand(productId: 1, messageId: 5)))
            ->toThrow(WhatsappConfigurationError::class);
    });

    it('allows removal of a non-default message', function () {
        $product = makeWhatsappProductForMessageGuardrail();
        $nonDefaultMessage = WhatsappMessage::fromPersistence(
            id: 6, productId: 1, phoneId: 10, localeId: 2, localeCode: 'en_US',
            message: 'Hello', isDefault: false, phone: '612345678', prefix: '34',
            createdAt: new DateTimeImmutable(), updatedAt: new DateTimeImmutable(),
        );

        /** @var MockInterface&ProductRepositoryPort $productRepo */
        $productRepo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&WhatsappMessageRepositoryPort $messageRepo */
        $messageRepo = Mockery::mock(WhatsappMessageRepositoryPort::class);
        /** @var MockInterface&TransactionPort $transaction */
        $transaction = Mockery::mock(TransactionPort::class);
        $transaction->shouldReceive('run')->once()->andReturnUsing(static fn (callable $cb): mixed => $cb());

        $productRepo->shouldReceive('findById')->once()->with(1)->andReturn($product);
        $productRepo->shouldReceive('save')->once()->andReturnUsing(static fn (Product $p): Product => $p);
        $messageRepo->shouldReceive('findById')->once()->with(6)->andReturn($nonDefaultMessage);
        $messageRepo->shouldReceive('delete')->once()->with(6);

        $handler = new RemoveWhatsappMessageHandler($productRepo, $messageRepo, $transaction);
        $handler->handle(new RemoveWhatsappMessageCommand(productId: 1, messageId: 6));

        expect(true)->toBeTrue();
    });
});
