<?php

declare(strict_types=1);

namespace Src\Products\Domain\Ports;

interface PasswordGeneratorPort
{
    /**
     * Generate a random password of the given length.
     * The result MUST only contain alphanumeric characters (a-z, A-Z, 0-9).
     */
    public function generate(int $length): string;
}
