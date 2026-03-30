<?php

declare(strict_types=1);

use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Errors\ProductMisconfigured;
use Src\Products\Domain\Services\Redirection\SimpleRedirectionStrategy;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Products\Domain\ValueObjects\RedirectionContext;
use Src\Products\Domain\ValueObjects\RedirectionType;

function buildProductWithModel(string $model): Product
{
    return Product::fromPersistence(
        id: 1,
        productTypeId: 1,
        userId: null,
        model: $model,
        linkedToProductId: null,
        password: 'secret',
        targetUrl: 'https://example.com',
        usage: 0,
        name: 'Test Product',
        description: null,
        active: true,
        configurationStatus: ConfigurationStatus::from(ConfigurationStatus::COMPLETED),
        assignedAt: null,
        size: null,
        createdAt: new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
        updatedAt: new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
        deletedAt: null,
    );
}

function buildProductWithTargetUrl(?string $targetUrl): Product
{
    return Product::fromPersistence(
        id: 1,
        productTypeId: 1,
        userId: null,
        model: 'google',
        linkedToProductId: null,
        password: 'secret',
        targetUrl: $targetUrl,
        usage: 0,
        name: 'Test Product',
        description: null,
        active: true,
        configurationStatus: ConfigurationStatus::from(ConfigurationStatus::COMPLETED),
        assignedAt: null,
        size: null,
        createdAt: new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
        updatedAt: new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
        deletedAt: null,
    );
}

describe('SimpleRedirectionStrategy', function () {
    beforeEach(function () {
        $this->strategy = new SimpleRedirectionStrategy();
        $this->context = new RedirectionContext('en-US');
    });

    describe('supports()', function () {
        it('returns true for google model', function () {
            expect($this->strategy->supports(buildProductWithModel('google')))->toBeTrue();
        });

        it('returns true for instagram model', function () {
            expect($this->strategy->supports(buildProductWithModel('instagram')))->toBeTrue();
        });

        it('returns true for youtube model', function () {
            expect($this->strategy->supports(buildProductWithModel('youtube')))->toBeTrue();
        });

        it('returns true for tiktok model', function () {
            expect($this->strategy->supports(buildProductWithModel('tiktok')))->toBeTrue();
        });

        it('returns true for facebook model', function () {
            expect($this->strategy->supports(buildProductWithModel('facebook')))->toBeTrue();
        });

        it('returns false for whatsapp model', function () {
            expect($this->strategy->supports(buildProductWithModel('whatsapp')))->toBeFalse();
        });

        it('returns false for bracelet model', function () {
            expect($this->strategy->supports(buildProductWithModel('bracelet')))->toBeFalse();
        });
    });

    describe('resolve()', function () {
        it('returns RedirectionTarget with external URL type when targetUrl is set', function () {
            $target = $this->strategy->resolve(buildProductWithTargetUrl('https://example.com'), $this->context);

            expect($target->url)->toBe('https://example.com')
                ->and($target->type)->toBe(RedirectionType::EXTERNAL_URL);
        });

        it('throws ProductMisconfigured when targetUrl is null', function () {
            expect(fn () => $this->strategy->resolve(buildProductWithTargetUrl(null), $this->context))
                ->toThrow(ProductMisconfigured::class);
        });

        it('passes through malformed URLs without validation', function () {
            $target = $this->strategy->resolve(buildProductWithTargetUrl('not-a-url'), $this->context);

            expect($target->url)->toBe('not-a-url');
        });
    });
});
