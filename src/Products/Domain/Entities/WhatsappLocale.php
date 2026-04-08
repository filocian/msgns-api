<?php

declare(strict_types=1);

namespace Src\Products\Domain\Entities;

final readonly class WhatsappLocale
{
    private function __construct(
        public int $id,
        public string $code,
    ) {}

    public static function fromPersistence(int $id, string $code): self
    {
        return new self(id: $id, code: $code);
    }
}
