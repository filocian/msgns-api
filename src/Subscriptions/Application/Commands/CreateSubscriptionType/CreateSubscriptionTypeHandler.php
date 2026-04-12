<?php

declare(strict_types=1);

namespace Src\Subscriptions\Application\Commands\CreateSubscriptionType;

use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Src\Identity\Domain\Permissions\DomainRoles;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Subscriptions\Application\Resources\SubscriptionTypeResource;
use Src\Subscriptions\Domain\Entities\SubscriptionType;
use Src\Subscriptions\Domain\Ports\SubscriptionTypeRepositoryPort;
use Src\Subscriptions\Domain\ValueObjects\BillingPeriod;
use Src\Subscriptions\Domain\ValueObjects\SubscriptionMode;

final class CreateSubscriptionTypeHandler implements CommandHandler
{
    public function __construct(
        private readonly SubscriptionTypeRepositoryPort $repo,
    ) {}

    public function handle(Command $command): SubscriptionTypeResource
    {
        assert($command instanceof CreateSubscriptionTypeCommand);

        $billingPeriods = $command->billingPeriods !== null
            ? array_map(static fn (string $v): BillingPeriod => BillingPeriod::from($v), $command->billingPeriods)
            : null;

        $subscriptionType = SubscriptionType::create(
            name: $command->name,
            slug: Str::slug($command->name),
            description: $command->description,
            mode: SubscriptionMode::from($command->mode),
            billingPeriods: $billingPeriods,
            basePriceCents: $command->basePriceCents,
            permissionName: $command->permissionName,
            googleReviewLimit: $command->googleReviewLimit,
            instagramContentLimit: $command->instagramContentLimit,
        );

        $saved = $this->repo->save($subscriptionType);

        Permission::findOrCreate($saved->permissionName, DomainRoles::GUARD);

        return SubscriptionTypeResource::fromEntity($saved);
    }
}
