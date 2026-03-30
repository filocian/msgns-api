<?php

declare(strict_types=1);

namespace Src\Products\Domain\ValueObjects;

final readonly class RedirectionContext
{
    public function __construct(
        public string $browserLocales,
    ) {}
}
