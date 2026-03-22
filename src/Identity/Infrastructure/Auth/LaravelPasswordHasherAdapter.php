<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Auth;

use Illuminate\Support\Facades\Hash;
use Src\Identity\Domain\Ports\PasswordHasherPort;

final class LaravelPasswordHasherAdapter implements PasswordHasherPort
{
    public function check(string $plaintext, string $hashed): bool
    {
        return Hash::check($plaintext, $hashed);
    }
}
