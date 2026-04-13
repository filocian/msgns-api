<?php

declare(strict_types=1);

namespace Src\Billing\Application\Resources;

final readonly class PaymentMethodResource
{
    public function __construct(
        public readonly string $id,
        public readonly string $brand,
        public readonly string $last_four,
        public readonly int $exp_month,
        public readonly int $exp_year,
        public readonly bool $is_default,
    ) {}
}
