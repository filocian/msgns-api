<?php

declare(strict_types=1);

namespace Src\Products\Application\Queries\ListWhatsappMessages;

use Src\Shared\Core\Bus\Query;

final readonly class ListWhatsappMessagesQuery implements Query
{
    public function __construct(
        public int $productId,
    ) {}

    public function queryName(): string
    {
        return 'products.list_whatsapp_messages';
    }
}
