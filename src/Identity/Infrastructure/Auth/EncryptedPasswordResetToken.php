<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Auth;

use Illuminate\Support\Facades\Crypt;
use Src\Identity\Domain\Ports\PasswordResetTokenPort;
use Src\Shared\Core\Errors\ValidationFailed;

final class EncryptedPasswordResetToken implements PasswordResetTokenPort
{
    private const TTL_DAYS = 1;

    public function generate(string $email): string
    {
        $inner = Crypt::encrypt($email . ';' . now()->toIso8601String());
        $outer = json_encode([
            'value'           => $inner,
            'expiration_date' => now()->addDays(self::TTL_DAYS)->toIso8601String(),
        ]);
        return Crypt::encrypt($outer);
    }

    public function validate(string $token): string
    {
        try {
            $outer = json_decode(Crypt::decrypt($token), true);
        } catch (\Throwable) {
            throw ValidationFailed::because('invalid_or_expired_token');
        }

        if (!isset($outer['expiration_date'], $outer['value'])) {
            throw ValidationFailed::because('invalid_or_expired_token');
        }

        if (now()->isAfter(\Carbon\Carbon::parse($outer['expiration_date']))) {
            throw ValidationFailed::because('invalid_or_expired_token');
        }

        try {
            $inner = Crypt::decrypt($outer['value']);
        } catch (\Throwable) {
            throw ValidationFailed::because('invalid_or_expired_token');
        }

        $parts = explode(';', $inner, 2);
        return $parts[0]; // email
    }
}
