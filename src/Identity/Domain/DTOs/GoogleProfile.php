<?php

declare(strict_types=1);

namespace Src\Identity\Domain\DTOs;

final readonly class GoogleProfile
{
    public function __construct(
        public string $email,
        public string $name,
        public string $googleId,
    ) {}
}
