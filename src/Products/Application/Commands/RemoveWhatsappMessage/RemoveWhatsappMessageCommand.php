<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\RemoveWhatsappMessage;

use Src\Shared\Core\Bus\Command;

final readonly class RemoveWhatsappMessageCommand implements Command
{
    public function __construct(
        public int $productId,
        public int $messageId,
    ) {}

    public function commandName(): string
    {
        return 'products.remove_whatsapp_message';
    }
}
