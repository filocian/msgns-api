<?php

declare(strict_types=1);

namespace Src\Products\Application\Resources;

use Src\Products\Domain\Entities\WhatsappPhone;

final readonly class WhatsappPhoneResource
{
    public function __construct(
        public int $id,
        public int $productId,
        public string $phone,
        public string $prefix,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromEntity(WhatsappPhone $phone): self
    {
        return new self(
            id: $phone->id,
            productId: $phone->productId,
            phone: $phone->phone,
            prefix: $phone->prefix,
            createdAt: $phone->createdAt->format('c'),
            updatedAt: $phone->updatedAt->format('c'),
        );
    }
}
