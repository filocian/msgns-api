<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\AddWhatsappPhone;

use Src\Shared\Core\Bus\Command;

final readonly class AddWhatsappPhoneCommand implements Command
{
    public function __construct(
        public int $productId,
        public string $phone,
        public string $prefix,
    ) {}

    public function commandName(): string
    {
        return 'products.add_whatsapp_phone';
    }
}
