<?php

declare(strict_types=1);

namespace Src\Ai\Application\Commands\SubscribeToClassicPlan;

use Src\Shared\Core\Bus\Command;

final readonly class SubscribeToClassicPlanCommand implements Command
{
    public function __construct(
        public int $userId,
        public int $subscriptionTypeId,
        public string $billingPeriod,
        public string $paymentMethodId,
    ) {}

    public function commandName(): string
    {
        return 'ai.subscribe_to_classic_plan';
    }
}
