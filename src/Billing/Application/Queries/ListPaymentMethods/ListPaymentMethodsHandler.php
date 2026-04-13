<?php

declare(strict_types=1);

namespace Src\Billing\Application\Queries\ListPaymentMethods;

use Src\Billing\Application\Resources\PaymentMethodResource;
use Src\Billing\Domain\Ports\BillingPort;
use Src\Shared\Core\Bus\Query;
use Src\Shared\Core\Bus\QueryHandler;

final class ListPaymentMethodsHandler implements QueryHandler
{
    public function __construct(
        private readonly BillingPort $billing,
    ) {}

    /**
     * @return list<PaymentMethodResource>
     */
    public function handle(Query $query): array
    {
        assert($query instanceof ListPaymentMethodsQuery);

        $methods = $this->billing->listPaymentMethods($query->userId);

        return array_map(
            static fn (array $m): PaymentMethodResource => new PaymentMethodResource(
                id: $m['id'],
                brand: $m['brand'],
                last_four: $m['last_four'],
                exp_month: $m['exp_month'],
                exp_year: $m['exp_year'],
                is_default: $m['is_default'],
            ),
            $methods,
        );
    }
}
