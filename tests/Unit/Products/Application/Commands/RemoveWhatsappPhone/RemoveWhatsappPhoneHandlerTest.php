<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Src\Products\Application\Commands\RemoveWhatsappPhone\RemoveWhatsappPhoneCommand;
use Src\Products\Application\Commands\RemoveWhatsappPhone\RemoveWhatsappPhoneHandler;
use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Entities\WhatsappPhone;
use Src\Products\Domain\Errors\WhatsappConfigurationError;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Ports\WhatsappPhoneRepositoryPort;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Ports\TransactionPort;

function makeWhatsappProductForGuardrail(int $id = 1): Product
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

describe('RemoveWhatsappPhoneHandler', function () {
    it('rejects removal of the last phone for a product', function () {
        $product = makeWhatsappProductForGuardrail();
        $phone = WhatsappPhone::fromPersistence(
            id: 10, productId: 1, phone: '612345678', prefix: '34',
            createdAt: new DateTimeImmutable(), updatedAt: new DateTimeImmutable(),
        );

        /** @var MockInterface&ProductRepositoryPort $productRepo */
        $productRepo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&WhatsappPhoneRepositoryPort $phoneRepo */
        $phoneRepo = Mockery::mock(WhatsappPhoneRepositoryPort::class);
        /** @var MockInterface&TransactionPort $transaction */
        $transaction = Mockery::mock(TransactionPort::class);
        $transaction->shouldReceive('run')->once()->andReturnUsing(static fn (callable $cb): mixed => $cb());

        $productRepo->shouldReceive('findById')->once()->with(1)->andReturn($product);
        $phoneRepo->shouldReceive('findById')->once()->with(10)->andReturn($phone);
        $phoneRepo->shouldReceive('countByProductId')->once()->with(1)->andReturn(1);
        $phoneRepo->shouldNotReceive('delete');

        $handler = new RemoveWhatsappPhoneHandler($productRepo, $phoneRepo, $transaction);

        expect(fn () => $handler->handle(new RemoveWhatsappPhoneCommand(productId: 1, phoneId: 10)))
            ->toThrow(WhatsappConfigurationError::class);
    });

    it('allows removal when multiple phones exist', function () {
        $product = makeWhatsappProductForGuardrail();
        $phone = WhatsappPhone::fromPersistence(
            id: 10, productId: 1, phone: '612345678', prefix: '34',
            createdAt: new DateTimeImmutable(), updatedAt: new DateTimeImmutable(),
        );

        /** @var MockInterface&ProductRepositoryPort $productRepo */
        $productRepo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&WhatsappPhoneRepositoryPort $phoneRepo */
        $phoneRepo = Mockery::mock(WhatsappPhoneRepositoryPort::class);
        /** @var MockInterface&TransactionPort $transaction */
        $transaction = Mockery::mock(TransactionPort::class);
        $transaction->shouldReceive('run')->once()->andReturnUsing(static fn (callable $cb): mixed => $cb());

        $productRepo->shouldReceive('findById')->once()->with(1)->andReturn($product);
        $productRepo->shouldReceive('save')->once()->andReturnUsing(static fn (Product $p): Product => $p);
        $phoneRepo->shouldReceive('findById')->once()->with(10)->andReturn($phone);
        $phoneRepo->shouldReceive('countByProductId')->once()->with(1)->andReturn(2);
        $phoneRepo->shouldReceive('delete')->once()->with(10);

        $handler = new RemoveWhatsappPhoneHandler($productRepo, $phoneRepo, $transaction);
        $handler->handle(new RemoveWhatsappPhoneCommand(productId: 1, phoneId: 10));

        // No exception → phone was deleted
        expect(true)->toBeTrue();
    });

    it('throws NotFound when phone does not belong to product', function () {
        $product = makeWhatsappProductForGuardrail();
        $phoneFromOtherProduct = WhatsappPhone::fromPersistence(
            id: 10, productId: 999, phone: '612345678', prefix: '34',
            createdAt: new DateTimeImmutable(), updatedAt: new DateTimeImmutable(),
        );

        /** @var MockInterface&ProductRepositoryPort $productRepo */
        $productRepo = Mockery::mock(ProductRepositoryPort::class);
        /** @var MockInterface&WhatsappPhoneRepositoryPort $phoneRepo */
        $phoneRepo = Mockery::mock(WhatsappPhoneRepositoryPort::class);
        /** @var MockInterface&TransactionPort $transaction */
        $transaction = Mockery::mock(TransactionPort::class);
        $transaction->shouldReceive('run')->once()->andReturnUsing(static fn (callable $cb): mixed => $cb());

        $productRepo->shouldReceive('findById')->once()->with(1)->andReturn($product);
        $phoneRepo->shouldReceive('findById')->once()->with(10)->andReturn($phoneFromOtherProduct);

        $handler = new RemoveWhatsappPhoneHandler($productRepo, $phoneRepo, $transaction);

        expect(fn () => $handler->handle(new RemoveWhatsappPhoneCommand(productId: 1, phoneId: 10)))
            ->toThrow(NotFound::class);
    });
});
