<?php

declare(strict_types=1);

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Shared\Core\Bus\PaginatedResult;
use Src\Subscriptions\Application\Commands\UpdateSubscriptionType\UpdateSubscriptionTypeCommand;
use Src\Subscriptions\Application\Commands\UpdateSubscriptionType\UpdateSubscriptionTypeHandler;
use Src\Subscriptions\Application\Resources\SubscriptionTypeResource;
use Src\Subscriptions\Domain\Entities\SubscriptionType;
use Src\Subscriptions\Domain\Errors\SubscriptionTypeNotFound;
use Src\Subscriptions\Domain\Ports\SubscriptionTypeRepositoryPort;
use Src\Subscriptions\Domain\ValueObjects\BillingPeriod;
use Src\Subscriptions\Domain\ValueObjects\SubscriptionMode;

uses(RefreshDatabase::class);

/**
 * @return SubscriptionTypeRepositoryPort&object{saved: list<SubscriptionType>, existing: ?SubscriptionType}
 */
function makeUpdateFakeRepo(?SubscriptionType $existing): SubscriptionTypeRepositoryPort
{
    return new class($existing) implements SubscriptionTypeRepositoryPort {
        /** @var list<SubscriptionType> */
        public array $saved = [];

        public function __construct(public ?SubscriptionType $existing) {}

        public function save(SubscriptionType $subscriptionType): SubscriptionType
        {
            $this->saved[] = $subscriptionType;
            return $subscriptionType;
        }

        public function findById(int $id): ?SubscriptionType
        {
            return $this->existing;
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
            return false;
        }
    };
}

function makeExistingSubscriptionType(): SubscriptionType
{
    $now = new DateTimeImmutable();

    return SubscriptionType::fromPersistence(
        id: 1,
        name: 'Old Name',
        slug: 'old-name',
        description: 'Old description',
        mode: SubscriptionMode::Classic,
        billingPeriods: [BillingPeriod::Monthly],
        basePriceCents: 999,
        permissionName: 'ai.old',
        googleReviewLimit: 5,
        instagramContentLimit: 2,
        stripeProductId: 'prod_xyz',
        stripePriceIds: ['monthly' => 'price_xyz'],
        isActive: true,
        createdAt: $now,
        updatedAt: $now,
    );
}

describe('UpdateSubscriptionTypeHandler', function () {
    it('updates mutable fields without touching stripe/mode/billing bindings', function () {
        $existing = makeExistingSubscriptionType();
        $repo = makeUpdateFakeRepo($existing);

        $handler = new UpdateSubscriptionTypeHandler($repo);

        $command = new UpdateSubscriptionTypeCommand(
            id: 1,
            name: 'New Name',
            description: 'New description',
            permissionName: 'ai.new',
            googleReviewLimit: 100,
            instagramContentLimit: 20,
        );

        $result = $handler->handle($command);

        expect($result)->toBeInstanceOf(SubscriptionTypeResource::class);
        expect($repo->saved)->toHaveCount(1);
        $saved = $repo->saved[0];
        // Mutable fields updated.
        expect($saved->name)->toBe('New Name');
        expect($saved->description)->toBe('New description');
        expect($saved->permissionName)->toBe('ai.new');
        expect($saved->googleReviewLimit)->toBe(100);
        expect($saved->instagramContentLimit)->toBe(20);
        // Immutable Stripe/billing fields untouched.
        expect($saved->stripeProductId)->toBe('prod_xyz');
        expect($saved->stripePriceIds)->toBe(['monthly' => 'price_xyz']);
        expect($saved->mode)->toBe(SubscriptionMode::Classic);
        expect($saved->billingPeriods)->toBe([BillingPeriod::Monthly]);
        expect($saved->basePriceCents)->toBe(999);
    });

    it('throws SubscriptionTypeNotFound when id does not exist', function () {
        $repo = makeUpdateFakeRepo(null);
        $handler = new UpdateSubscriptionTypeHandler($repo);

        $command = new UpdateSubscriptionTypeCommand(
            id: 999,
            name: 'Whatever',
            description: null,
            permissionName: 'ai.whatever',
            googleReviewLimit: 1,
            instagramContentLimit: 1,
        );

        $handler->handle($command);
    })->throws(SubscriptionTypeNotFound::class);

    it('reduced UpdateSubscriptionTypeCommand no longer accepts mode/basePriceCents/billingPeriods', function () {
        $ref = new ReflectionClass(UpdateSubscriptionTypeCommand::class);
        $ctor = $ref->getConstructor();
        expect($ctor)->not->toBeNull();
        /** @var ReflectionMethod $ctor */
        $paramNames = array_map(fn ($p) => $p->getName(), $ctor->getParameters());

        expect($paramNames)->not->toContain('mode');
        expect($paramNames)->not->toContain('basePriceCents');
        expect($paramNames)->not->toContain('billingPeriods');
        expect($paramNames)->not->toContain('stripeProductId');
    });
});
