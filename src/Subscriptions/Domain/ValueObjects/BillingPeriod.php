<?php

declare(strict_types=1);

namespace Src\Subscriptions\Domain\ValueObjects;

enum BillingPeriod: string
{
    case Monthly = 'monthly';
    case Annual  = 'annual';
    case OneTime = 'one_time';
}
