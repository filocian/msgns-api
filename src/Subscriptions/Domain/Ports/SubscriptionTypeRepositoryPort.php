<?php

declare(strict_types=1);

namespace Src\Subscriptions\Domain\Ports;

use Src\Shared\Core\Bus\PaginatedResult;
use Src\Subscriptions\Domain\Entities\SubscriptionType;

interface SubscriptionTypeRepositoryPort
{
    public function save(SubscriptionType $subscriptionType): SubscriptionType;

    public function findById(int $id): ?SubscriptionType;

    /**
     * Returns paginated admin list with optional filters.
     * Follow the ProductTypeRepository::list() pattern — return PaginatedResult directly.
     */
    public function listAdmin(
        int $page,
        int $perPage,
        string $sortBy,
        string $sortDir,
        ?string $mode,
        ?bool $isActive,
    ): PaginatedResult;

    /** @return list<SubscriptionType> */
    public function listPublicActive(): array;

    /**
     * Check if any active user subscriptions reference this type.
     * Used as a guard before soft-delete.
     */
    public function hasActiveSubscriptions(int $subscriptionTypeId): bool;

    public function softDelete(int $id): void;

    /**
     * Returns true if any existing subscription type is bound to the given Stripe product id.
     *
     * Used by the CreateSubscriptionTypeHandler as an application-level guard
     * against duplicates before the DB unique index would surface a constraint
     * violation. See REQ-007.
     */
    public function existsByStripeProductId(string $stripeProductId): bool;
}
