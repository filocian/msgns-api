<?php

declare(strict_types=1);

namespace Src\Subscriptions\Application\Commands\CreateSubscriptionType;

use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Src\Billing\Domain\DataTransferObjects\StripeCatalogPrice;
use Src\Billing\Domain\Errors\StripeProductUnavailable;
use Src\Billing\Domain\Ports\StripeCatalogPort;
use Src\Identity\Domain\Permissions\DomainRoles;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
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

final class CreateSubscriptionTypeHandler implements CommandHandler
{
    public function __construct(
        private readonly SubscriptionTypeRepositoryPort $repo,
        private readonly StripeCatalogPort $catalog,
    ) {}

    public function handle(Command $command): SubscriptionTypeResource
    {
        assert($command instanceof CreateSubscriptionTypeCommand);

        // 1. Duplicate check at application level — guards REQ-007.
        if ($this->repo->existsByStripeProductId($command->stripeProductId)) {
            throw SubscriptionTypeStripeProductDuplicate::withProductId($command->stripeProductId);
        }

        // 2. Fetch Stripe product — rethrow port-level unavailable as subscription-scoped not-found.
        try {
            $product = $this->catalog->getProduct($command->stripeProductId);
        } catch (StripeProductUnavailable) {
            throw SubscriptionTypeStripeProductNotFound::withProductId($command->stripeProductId);
        }
        if (!$product->active) {
            throw SubscriptionTypeStripeProductNotFound::withProductId($command->stripeProductId);
        }

        // 3. Fetch prices for the product; filter to active only.
        $prices = array_values(array_filter(
            $this->catalog->listPricesForProduct($command->stripeProductId),
            static fn (StripeCatalogPrice $p): bool => $p->active,
        ));

        // 4. Mixed-type guard.
        $types = array_unique(array_map(static fn (StripeCatalogPrice $p): string => $p->type, $prices));
        if (count($types) > 1) {
            throw SubscriptionTypeStripeProductMixedPrices::withProductId($command->stripeProductId);
        }
        if ($prices === []) {
            // No prices — treat as not-found (product unusable).
            throw SubscriptionTypeStripeProductNotFound::withProductId($command->stripeProductId);
        }

        $priceType = $prices[0]->type;

        // 5-7. Map interval → label, build stripePriceIds map.
        /** @var array<string, StripeCatalogPrice> $pricesByLabel */
        $pricesByLabel = [];
        foreach ($prices as $price) {
            $label = self::mapInterval($price, $command->stripeProductId);
            $pricesByLabel[$label] = $price;
        }

        // 8. Mode derivation.
        $mode = $priceType === 'recurring' ? SubscriptionMode::Classic : SubscriptionMode::Prepaid;

        // 9. No-monthly guard (recurring only).
        if ($mode === SubscriptionMode::Classic && !isset($pricesByLabel['monthly'])) {
            throw SubscriptionTypeStripeProductNoMonthlyPrice::withProductId($command->stripeProductId);
        }

        // 10. Primary price selection.
        $primary = $mode === SubscriptionMode::Classic
            ? $pricesByLabel['monthly']
            : $pricesByLabel['one_time'];

        // 11. Currency guard (EUR-only, case-insensitive).
        if (strtolower($primary->currency) !== 'eur') {
            throw SubscriptionTypeStripeProductInvalidCurrency::withProductId(
                $command->stripeProductId,
                $primary->currency,
            );
        }

        // 12. basePriceCents from primary price.
        $basePriceCents = $primary->unitAmount;

        // 13. billingPeriods & stripePriceIds map.
        /** @var array<string, string> $stripePriceIds */
        $stripePriceIds = [];
        /** @var list<BillingPeriod> $billingPeriods */
        $billingPeriods = [];
        foreach ($pricesByLabel as $label => $price) {
            $stripePriceIds[$label] = $price->id;
            $billingPeriods[]       = BillingPeriod::from($label);
        }

        // 14. Entity create.
        $subscriptionType = SubscriptionType::create(
            name: $command->name,
            slug: Str::slug($command->name),
            description: $command->description,
            mode: $mode,
            billingPeriods: $billingPeriods,
            basePriceCents: $basePriceCents,
            permissionName: $command->permissionName,
            googleReviewLimit: $command->googleReviewLimit,
            instagramContentLimit: $command->instagramContentLimit,
            stripeProductId: $command->stripeProductId,
            stripePriceIds: $stripePriceIds,
        );

        // 15. Persist.
        $saved = $this->repo->save($subscriptionType);

        // 16. Permission sync (pre-existing behaviour).
        Permission::findOrCreate($saved->permissionName, DomainRoles::GUARD);

        return SubscriptionTypeResource::fromEntity($saved);
    }

    /**
     * Maps a Stripe price (type + interval) to the app-level billing-period label.
     *
     * Any unsupported combination raises a defensive MixedPrices error.
     */
    private static function mapInterval(StripeCatalogPrice $price, string $productId): string
    {
        if ($price->type === 'recurring' && $price->interval === 'month') {
            return 'monthly';
        }
        if ($price->type === 'recurring' && $price->interval === 'year') {
            return 'annual';
        }
        if ($price->type === 'one_time' && $price->interval === null) {
            return 'one_time';
        }

        throw SubscriptionTypeStripeProductMixedPrices::withProductId($productId);
    }
}
