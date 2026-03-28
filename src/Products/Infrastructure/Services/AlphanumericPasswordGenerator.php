<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Services;

use Src\Products\Domain\Ports\PasswordGeneratorPort;

final class AlphanumericPasswordGenerator implements PasswordGeneratorPort
{
    private const string CHARSET = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    public function generate(int $length): string
    {
        $charset = self::CHARSET;
        $charsetLength = strlen($charset);
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $charset[random_int(0, $charsetLength - 1)];
        }

        return $password;
    }
}
