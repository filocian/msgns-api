<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\AddWhatsappMessage;

use Src\Shared\Core\Bus\Command;

final readonly class AddWhatsappMessageCommand implements Command
{
    public function __construct(
        public int $productId,
        public int $phoneId,
        public string $localeCode,
        public string $message,
    ) {}

    public function commandName(): string
    {
        return 'products.add_whatsapp_message';
    }
}
