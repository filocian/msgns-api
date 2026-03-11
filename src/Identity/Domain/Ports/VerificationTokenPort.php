<?php

declare(strict_types=1);

namespace Src\Identity\Domain\Ports;

interface VerificationTokenPort
{
    public function generate(string $email): string;
    public function validate(string $token): string; // returns email or throws
}
