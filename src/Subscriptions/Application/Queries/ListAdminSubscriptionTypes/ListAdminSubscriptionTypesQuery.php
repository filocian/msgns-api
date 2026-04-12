<?php

declare(strict_types=1);

namespace Src\Subscriptions\Application\Queries\ListAdminSubscriptionTypes;

use Src\Shared\Core\Bus\Query;

final readonly class ListAdminSubscriptionTypesQuery implements Query
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 15,
        public string $sortBy = 'name',
        public string $sortDir = 'asc',
        public ?string $mode = null,
        public ?bool $isActive = null,
    ) {}

    public function queryName(): string
    {
        return 'subscriptions.list_admin_subscription_types';
    }
}
