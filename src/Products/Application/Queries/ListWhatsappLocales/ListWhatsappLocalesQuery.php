<?php

declare(strict_types=1);

namespace Src\Products\Application\Queries\ListWhatsappLocales;

use Src\Shared\Core\Bus\Query;

final readonly class ListWhatsappLocalesQuery implements Query
{
    public function __construct() {}

    public function queryName(): string
    {
        return 'products.list_whatsapp_locales';
    }
}
