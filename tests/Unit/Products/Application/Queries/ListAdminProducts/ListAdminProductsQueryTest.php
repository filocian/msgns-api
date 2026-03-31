<?php

declare(strict_types=1);

use Src\Products\Application\Queries\ListAdminProducts\ListAdminProductsQuery;

describe('ListAdminProductsQuery', function () {
    it('exposes the expected defaults', function () {
        $query = new ListAdminProductsQuery();

        expect($query->page)->toBe(1)
            ->and($query->perPage)->toBe(15)
            ->and($query->sortBy)->toBe('assigned_at')
            ->and($query->sortDir)->toBe('desc')
            ->and($query->productTypeCode)->toBeNull()
            ->and($query->productTypeId)->toBeNull()
            ->and($query->model)->toBeNull()
            ->and($query->name)->toBeNull()
            ->and($query->userId)->toBeNull()
            ->and($query->userEmail)->toBeNull()
            ->and($query->assignedAtFrom)->toBeNull()
            ->and($query->assignedAtTo)->toBeNull()
            ->and($query->configurationStatus)->toBeNull()
            ->and($query->active)->toBeNull()
            ->and($query->targetUrl)->toBeNull()
            ->and($query->businessType)->toBeNull()
            ->and($query->businessSize)->toBeNull()
            ->and($query->queryName())->toBe('products.list_admin_products');
    });

    it('stores all pagination sorting and filter parameters', function () {
        $query = new ListAdminProductsQuery(
            page: 3,
            perPage: 50,
            sortBy: 'created_at',
            sortDir: 'asc',
            productTypeCode: 'nfc-card',
            productTypeId: 7,
            model: 'nfc',
            name: 'Admin Card',
            userId: 12,
            userEmail: 'john@example.com',
            assignedAtFrom: '2025-01-01',
            assignedAtTo: '2025-12-31',
            configurationStatus: 'completed',
            active: true,
            targetUrl: 'example.com',
            businessType: 'restaurant',
            businessSize: 'small',
        );

        expect($query->page)->toBe(3)
            ->and($query->perPage)->toBe(50)
            ->and($query->sortBy)->toBe('created_at')
            ->and($query->sortDir)->toBe('asc')
            ->and($query->productTypeCode)->toBe('nfc-card')
            ->and($query->productTypeId)->toBe(7)
            ->and($query->model)->toBe('nfc')
            ->and($query->name)->toBe('Admin Card')
            ->and($query->userId)->toBe(12)
            ->and($query->userEmail)->toBe('john@example.com')
            ->and($query->assignedAtFrom)->toBe('2025-01-01')
            ->and($query->assignedAtTo)->toBe('2025-12-31')
            ->and($query->configurationStatus)->toBe('completed')
            ->and($query->active)->toBeTrue()
            ->and($query->targetUrl)->toBe('example.com')
            ->and($query->businessType)->toBe('restaurant')
            ->and($query->businessSize)->toBe('small');
    });
});
