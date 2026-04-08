<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\RemoveWhatsappPhone;

use Src\Shared\Core\Bus\Command;

final readonly class RemoveWhatsappPhoneCommand implements Command
{
    public function __construct(
        public int $productId,
        public int $phoneId,
    ) {}

    public function commandName(): string
    {
        return 'products.remove_whatsapp_phone';
    }
}
