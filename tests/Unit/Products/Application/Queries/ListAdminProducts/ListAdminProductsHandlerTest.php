<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Mockery\Expectation;
use Src\Products\Application\Queries\ListAdminProducts\ListAdminProductsHandler;
use Src\Products\Application\Queries\ListAdminProducts\ListAdminProductsQuery;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Shared\Core\Bus\PaginatedResult;

afterEach(fn () => Mockery::close());

describe('ListAdminProductsHandler', function () {
    it('delegates the query payload to the repository and returns the paginated result', function () {
        $expected = new PaginatedResult(
            items: [['id' => 42, 'name' => 'Admin Product']],
            currentPage: 2,
            perPage: 25,
            total: 1,
            lastPage: 1,
        );

        /** @var MockInterface&ProductRepositoryPort $repository */
        $repository = Mockery::mock(ProductRepositoryPort::class);
        /** @var Expectation $expectation */
        $expectation = $repository->shouldReceive('listForAdmin');
        $expectation
            ->once()
             ->with([
                 'page' => 2,
                 'perPage' => 25,
                 'sortBy' => 'created_at',
                 'sortDir' => 'asc',
                'productTypeCode' => 'nfc-card',
                'productTypeId' => 9,
                'model' => 'nfc',
                'name' => 'Admin Card',
                'userId' => 17,
                'userEmail' => 'john@example.com',
                'assignedAtFrom' => '2025-01-01',
                'assignedAtTo' => '2025-01-31',
                'configurationStatus' => 'completed',
                'active' => false,
                 'targetUrl' => 'shop.example.com',
                 'businessType' => 'restaurant',
                 'businessSize' => 'small',
                 'timezone' => null,
             ])
             ->andReturn($expected);

        $handler = new ListAdminProductsHandler($repository);

        $result = $handler->handle(new ListAdminProductsQuery(
            page: 2,
            perPage: 25,
            sortBy: 'created_at',
            sortDir: 'asc',
            productTypeCode: 'nfc-card',
            productTypeId: 9,
            model: 'nfc',
            name: 'Admin Card',
            userId: 17,
            userEmail: 'john@example.com',
            assignedAtFrom: '2025-01-01',
            assignedAtTo: '2025-01-31',
            configurationStatus: 'completed',
             active: false,
             targetUrl: 'shop.example.com',
             businessType: 'restaurant',
             businessSize: 'small',
             timezone: null,
         ));

        expect($result)->toBe($expected);
    });
});
