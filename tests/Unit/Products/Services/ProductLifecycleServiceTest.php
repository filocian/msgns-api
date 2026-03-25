<?php

declare(strict_types=1);

use Mockery;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Services\ProductLifecycleService;

describe('ProductLifecycleService', function () {

    beforeEach(function () {
        $this->productRepo = Mockery::mock(ProductRepositoryPort::class);
        $this->service = new ProductLifecycleService($this->productRepo);
    });

    it('soft deletes a product', function () {
        $this->productRepo->shouldReceive('delete')
            ->once()
            ->with(1);

        $this->service->softDelete(1);
    });

    it('restores a product', function () {
        $this->productRepo->shouldReceive('restore')
            ->once()
            ->with(1);

        $this->service->restore(1);
    });
});
