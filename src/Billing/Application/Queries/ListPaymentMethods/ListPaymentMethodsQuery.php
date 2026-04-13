<?php

declare(strict_types=1);

namespace Src\Billing\Application\Queries\ListPaymentMethods;

use Src\Shared\Core\Bus\Query;

final readonly class ListPaymentMethodsQuery implements Query
{
    public function __construct(
        public int $userId,
    ) {}

    public function queryName(): string
    {
        return 'billing.list_payment_methods';
    }
}
