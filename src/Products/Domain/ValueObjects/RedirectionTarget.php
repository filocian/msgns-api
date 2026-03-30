<?php

declare(strict_types=1);

namespace Src\Products\Domain\ValueObjects;

final readonly class RedirectionTarget
{
    private function __construct(
        public string $url,
        public RedirectionType $type,
    ) {}

    public static function externalUrl(string $url): self
    {
        return new self($url, RedirectionType::EXTERNAL_URL);
    }
}
