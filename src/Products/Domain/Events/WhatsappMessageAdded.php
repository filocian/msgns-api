<?php

declare(strict_types=1);

namespace Src\Products\Domain\Events;

use Src\Shared\Core\Bus\DomainEvent;

final readonly class WhatsappMessageAdded implements DomainEvent
{
    public function __construct(
        public int $productId,
        public int $messageId,
    ) {}

    public function eventName(): string
    {
        return 'products.whatsapp_message_added';
    }
}
