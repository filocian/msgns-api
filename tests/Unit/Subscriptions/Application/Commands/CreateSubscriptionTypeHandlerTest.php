<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Billing\Domain\DataTransferObjects\StripeCatalogPrice;
use Src\Billing\Domain\DataTransferObjects\StripeCatalogProduct;
use Src\Billing\Domain\Errors\StripeProductUnavailable;
use Src\Billing\Domain\Ports\StripeCatalogPort;
use Src\Shared\Core\Bus\PaginatedResult;
use Src\Subscriptions\Application\Commands\CreateSubscriptionType\CreateSubscriptionTypeCommand;
use Src\Subscriptions\Application\Commands\CreateSubscriptionType\CreateSubscriptionTypeHandler;
use Src\Subscriptions\Application\Resources\SubscriptionTypeResource;
use Src\Subscriptions\Domain\Entities\SubscriptionType;
use Src\Subscriptions\Domain\Errors\SubscriptionTypeStripeProductDuplicate;
use Src\Subscriptions\Domain\Errors\SubscriptionTypeStripeProductInvalidCurrency;
use Src\Subscriptions\Domain\Errors\SubscriptionTypeStripeProductMixedPrices;
use Src\Subscriptions\Domain\Errors\SubscriptionTypeStripeProductNoMonthlyPrice;
use Src\Subscriptions\Domain\Errors\SubscriptionTypeStripeProductNotFound;
use Src\Subscriptions\Domain\Ports\SubscriptionTypeRepositoryPort;
use Src\Subscriptions\Domain\ValueObjects\BillingPeriod;
use Src\Subscriptions\Domain\ValueObjects\SubscriptionMode;

/**
 * In-memory fake repository.
 */
function makeFakeRepo(bool $existsByStripeProductId = false): SubscriptionTypeRepositoryPort
{
    return new class($existsByStripeProductId) implements SubscriptionTypeRepositoryPort {
        /** @var list<SubscriptionType> */
        public array $saved = [];

        public bool $existsCalledWith = false;

        public function __construct(private readonly bool $exists) {}

        public function save(SubscriptionType $subscriptionType): SubscriptionType
        {
            // Re-hydrate via fromPersistence to assign a synthetic id.
            $saved = SubscriptionType::fromPersistence(
                id: 1,
                name: $subscriptionType->name,
                slug: $subscriptionType->slug,
                description: $subscriptionType->description,
                mode: $subscriptionType->mode,
                billingPeriods: $subscriptionType->billingPeriods,
                basePriceCents: $subscriptionType->basePriceCents,
                permissionName: $subscriptionType->permissionName,
                googleReviewLimit: $subscriptionType->googleReviewLimit,
                instagramContentLimit: $subscriptionType->instagramContentLimit,
                stripeProductId: $subscriptionType->stripeProductId,
                stripePriceIds: $subscriptionType->stripePriceIds,
                isActive: $subscriptionType->isActive,
                createdAt: $subscriptionType->createdAt,
                updatedAt: $subscriptionType->updatedAt,
            );
            $this->saved[] = $saved;
            return $saved;
        }

        public function findById(int $id): ?SubscriptionType
        {
            return null;
        }

        public function listAdmin(
            int $page,
            int $perPage,
            string $sortBy,
            string $sortDir,
            ?string $mode,
            ?bool $isActive,
        ): PaginatedResult {
            return new PaginatedResult([], 0, $page, $perPage);
        }

        public function listPublicActive(): array
        {
            return [];
        }

        public function hasActiveSubscriptions(int $subscriptionTypeId): bool
        {
            return false;
        }

        public function softDelete(int $id): void {}

        public function existsByStripeProductId(string $stripeProductId): bool
        {
            return $this->exists;
        }
    };
}

/**
 * In-memory fake StripeCatalogPort.
 *
 * @param list<StripeCatalogPrice> $prices
 */
function makeFakeCatalog(
    ?StripeCatalogProduct $product = null,
    array $prices = [],
    bool $throwUnavailable = false,
): StripeCatalogPort {
    return new class($product, $prices, $throwUnavailable) implements StripeCatalogPort {
        /** @param list<StripeCatalogPrice> $prices */
        public function __construct(
            private readonly ?StripeCatalogProduct $product,
            private readonly array $prices,
            private readonly bool $throwUnavailable,
        ) {}

        public function listProducts(): array
        {
            return $this->product !== null ? [$this->product] : [];
        }

        public function getProduct(string $productId): StripeCatalogProduct
        {
            if ($this->throwUnavailable) {
                throw StripeProductUnavailable::withProductId($productId);
            }
            return $this->product ?? new StripeCatalogProduct(
                id: $productId,
                name: 'Stub',
                active: true,
                prices: [],
                metadata: [],
            );
        }

        public function listPricesForProduct(string $productId): array
        {
            return $this->prices;
        }
    };
}

function makeCreateCommand(string $stripeProductId = 'prod_abc'): CreateSubscriptionTypeCommand
{
    return new CreateSubscriptionTypeCommand(
        name: 'Pro',
        description: null,
        permissionName: 'ai.pro',
        googleReviewLimit: 10,
        instagramContentLimit: 5,
        stripeProductId: $stripeProductId,
    );
}

uses(RefreshDatabase::class);

describe('CreateSubscriptionTypeHandler', function () {
    it('creates a classic recurring subscription type with monthly + annual EUR prices', function () {
        $product = new StripeCatalogProduct(
            id: 'prod_abc',
            name: 'Pro',
            active: true,
            prices: [],
            metadata: [],
        );
        $prices = [
            new StripeCatalogPrice(
                id: 'price_monthly',
                productId: 'prod_abc',
                currency: 'eur',
                unitAmount: 999,
                type: 'recurring',
                interval: 'month',
                active: true,
            ),
            new StripeCatalogPrice(
                id: 'price_annual',
                productId: 'prod_abc',
                currency: 'eur',
                unitAmount: 9990,
                type: 'recurring',
                interval: 'year',
                active: true,
            ),
        ];
        $repo = makeFakeRepo(existsByStripeProductId: false);
        $catalog = makeFakeCatalog($product, $prices);

        $handler = new CreateSubscriptionTypeHandler($repo, $catalog);

        $result = $handler->handle(makeCreateCommand());

        expect($result)->toBeInstanceOf(SubscriptionTypeResource::class);
        expect($repo->saved)->toHaveCount(1);
        $saved = $repo->saved[0];
        expect($saved->mode)->toBe(SubscriptionMode::Classic);
        expect($saved->billingPeriods)->toBe([BillingPeriod::Monthly, BillingPeriod::Annual]);
        expect($saved->basePriceCents)->toBe(999);
        expect($saved->stripePriceIds)->toBe([
            'monthly' => 'price_monthly',
            'annual'  => 'price_annual',
        ]);
        expect($saved->stripeProductId)->toBe('prod_abc');
    });

    it('creates a prepaid one_time subscription type with EUR price', function () {
        $product = new StripeCatalogProduct(
            id: 'prod_prepaid',
            name: 'Prepaid',
            active: true,
            prices: [],
            metadata: [],
        );
        $prices = [
            new StripeCatalogPrice(
                id: 'price_one_time',
                productId: 'prod_prepaid',
                currency: 'eur',
                unitAmount: 4999,
                type: 'one_time',
                interval: null,
                active: true,
            ),
        ];
        $repo = makeFakeRepo();
        $catalog = makeFakeCatalog($product, $prices);

        $handler = new CreateSubscriptionTypeHandler($repo, $catalog);

        $handler->handle(makeCreateCommand('prod_prepaid'));

        expect($repo->saved)->toHaveCount(1);
        $saved = $repo->saved[0];
        expect($saved->mode)->toBe(SubscriptionMode::Prepaid);
        expect($saved->billingPeriods)->toBe([BillingPeriod::OneTime]);
        expect($saved->basePriceCents)->toBe(4999);
        expect($saved->stripePriceIds)->toBe([
            'one_time' => 'price_one_time',
        ]);
    });

    it('rethrows StripeProductUnavailable as SubscriptionTypeStripeProductNotFound', function () {
        $repo = makeFakeRepo();
        $catalog = makeFakeCatalog(throwUnavailable: true);

        $handler = new CreateSubscriptionTypeHandler($repo, $catalog);

        $handler->handle(makeCreateCommand());
    })->throws(SubscriptionTypeStripeProductNotFound::class);

    it('rejects an inactive Stripe product as not found', function () {
        $product = new StripeCatalogProduct(
            id: 'prod_abc',
            name: 'Pro',
            active: false,
            prices: [],
            metadata: [],
        );
        $repo = makeFakeRepo();
        $catalog = makeFakeCatalog($product, []);

        $handler = new CreateSubscriptionTypeHandler($repo, $catalog);

        $handler->handle(makeCreateCommand());
    })->throws(SubscriptionTypeStripeProductNotFound::class);

    it('rejects mixed recurring + one_time prices', function () {
        $product = new StripeCatalogProduct(
            id: 'prod_abc',
            name: 'Pro',
            active: true,
            prices: [],
            metadata: [],
        );
        $prices = [
            new StripeCatalogPrice(
                id: 'price_monthly',
                productId: 'prod_abc',
                currency: 'eur',
                unitAmount: 999,
                type: 'recurring',
                interval: 'month',
                active: true,
            ),
            new StripeCatalogPrice(
                id: 'price_one_time',
                productId: 'prod_abc',
                currency: 'eur',
                unitAmount: 4999,
                type: 'one_time',
                interval: null,
                active: true,
            ),
        ];
        $repo = makeFakeRepo();
        $catalog = makeFakeCatalog($product, $prices);

        $handler = new CreateSubscriptionTypeHandler($repo, $catalog);

        $handler->handle(makeCreateCommand());
    })->throws(SubscriptionTypeStripeProductMixedPrices::class);

    it('rejects recurring prices without a monthly interval', function () {
        $product = new StripeCatalogProduct(
            id: 'prod_abc',
            name: 'Pro',
            active: true,
            prices: [],
            metadata: [],
        );
        $prices = [
            new StripeCatalogPrice(
                id: 'price_annual',
                productId: 'prod_abc',
                currency: 'eur',
                unitAmount: 9990,
                type: 'recurring',
                interval: 'year',
                active: true,
            ),
        ];
        $repo = makeFakeRepo();
        $catalog = makeFakeCatalog($product, $prices);

        $handler = new CreateSubscriptionTypeHandler($repo, $catalog);

        $handler->handle(makeCreateCommand());
    })->throws(SubscriptionTypeStripeProductNoMonthlyPrice::class);

    it('rejects non-EUR currency on primary price', function () {
        $product = new StripeCatalogProduct(
            id: 'prod_abc',
            name: 'Pro',
            active: true,
            prices: [],
            metadata: [],
        );
        $prices = [
            new StripeCatalogPrice(
                id: 'price_monthly',
                productId: 'prod_abc',
                currency: 'usd',
                unitAmount: 999,
                type: 'recurring',
                interval: 'month',
                active: true,
            ),
        ];
        $repo = makeFakeRepo();
        $catalog = makeFakeCatalog($product, $prices);

        $handler = new CreateSubscriptionTypeHandler($repo, $catalog);

        $handler->handle(makeCreateCommand());
    })->throws(SubscriptionTypeStripeProductInvalidCurrency::class);

    it('rejects duplicate stripe product id', function () {
        $product = new StripeCatalogProduct(
            id: 'prod_abc',
            name: 'Pro',
            active: true,
            prices: [],
            metadata: [],
        );
        $prices = [
            new StripeCatalogPrice(
                id: 'price_monthly',
                productId: 'prod_abc',
                currency: 'eur',
                unitAmount: 999,
                type: 'recurring',
                interval: 'month',
                active: true,
            ),
        ];
        $repo = makeFakeRepo(existsByStripeProductId: true);
        $catalog = makeFakeCatalog($product, $prices);

        $handler = new CreateSubscriptionTypeHandler($repo, $catalog);

        $handler->handle(makeCreateCommand());
    })->throws(SubscriptionTypeStripeProductDuplicate::class);
});
