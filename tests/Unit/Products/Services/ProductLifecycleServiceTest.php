<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Services\ProductLifecycleService;

describe('ProductLifecycleService', function () {

    it('soft deletes a product', function () {
        /** @var MockInterface&ProductRepositoryPort $productRepo */
        $productRepo = Mockery::mock(ProductRepositoryPort::class);
        $service = new ProductLifecycleService($productRepo);

        $productRepo->shouldReceive('delete')->once()->with(1); // @phpstan-ignore-line

        $service->softDelete(1);
    });

    it('restores a product', function () {
        /** @var MockInterface&ProductRepositoryPort $productRepo */
        $productRepo = Mockery::mock(ProductRepositoryPort::class);
        $service = new ProductLifecycleService($productRepo);

        $productRepo->shouldReceive('restore')->once()->with(1); // @phpstan-ignore-line

        $service->restore(1);
    });
});
