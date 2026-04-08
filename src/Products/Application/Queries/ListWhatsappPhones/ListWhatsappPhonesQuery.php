<?php

declare(strict_types=1);

namespace Src\Products\Application\Queries\ListWhatsappPhones;

use Src\Shared\Core\Bus\Query;

final readonly class ListWhatsappPhonesQuery implements Query
{
    public function __construct(
        public int $productId,
    ) {}

    public function queryName(): string
    {
        return 'products.list_whatsapp_phones';
    }
}
