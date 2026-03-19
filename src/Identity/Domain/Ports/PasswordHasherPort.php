<?php

declare(strict_types=1);

namespace Src\Identity\Domain\Ports;

interface PasswordHasherPort
{
    public function check(string $plaintext, string $hashed): bool;
}
