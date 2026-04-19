<?php

declare(strict_types=1);

namespace Src\Subscriptions\Domain\ValueObjects;

enum SubscriptionMode: string
{
    case Classic = 'classic';
    case Prepaid = 'prepaid';
}
