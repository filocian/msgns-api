<?php

declare(strict_types=1);

namespace Src\Billing\Application\Resources;

final readonly class SetupIntentResource
{
    public function __construct(public readonly string $client_secret) {}
}
