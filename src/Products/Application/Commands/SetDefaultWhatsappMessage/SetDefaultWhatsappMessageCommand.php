<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\SetDefaultWhatsappMessage;

use Src\Shared\Core\Bus\Command;

final readonly class SetDefaultWhatsappMessageCommand implements Command
{
    public function __construct(
        public int $productId,
        public int $messageId,
    ) {}

    public function commandName(): string
    {
        return 'products.set_default_whatsapp_message';
    }
}
