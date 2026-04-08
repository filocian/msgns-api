<?php

declare(strict_types=1);

namespace Src\Products\Application\Resources;

use Src\Products\Domain\Entities\WhatsappMessage;

final readonly class WhatsappMessageResource
{
    public function __construct(
        public int $id,
        public int $productId,
        public int $phoneId,
        public int $localeId,
        public string $localeCode,
        public string $message,
        public bool $isDefault,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromEntity(WhatsappMessage $message): self
    {
        return new self(
            id: $message->id,
            productId: $message->productId,
            phoneId: $message->phoneId,
            localeId: $message->localeId,
            localeCode: $message->localeCode,
            message: $message->message,
            isDefault: $message->isDefault,
            createdAt: $message->createdAt->format('c'),
            updatedAt: $message->updatedAt->format('c'),
        );
    }
}
