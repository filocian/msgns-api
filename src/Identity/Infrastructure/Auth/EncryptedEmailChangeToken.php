<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Auth;

use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Src\Identity\Domain\Ports\EmailChangeTokenPort;
use Src\Shared\Core\Errors\ValidationFailed;

final class EncryptedEmailChangeToken implements EmailChangeTokenPort
{
    private const TTL_MINUTES = 60;

    public function generate(int $userId, string $newEmail): string
    {
        $inner = Crypt::encrypt('email_change;' . $userId . ';' . $newEmail . ';' . now()->toIso8601String());
        $outer = json_encode([
            'value'           => $inner,
            'expiration_date' => now()->addMinutes(self::TTL_MINUTES)->toIso8601String(),
        ]);

        if ($outer === false) {
            throw new \RuntimeException('Failed to encode email change token payload.');
        }

        return Crypt::encrypt($outer);
    }

    /** @return array{userId: int, newEmail: string} */
    public function validate(string $token): array
    {
        try {
            /** @var mixed $outer */
            $outer = json_decode(Crypt::decrypt($token), true);
        } catch (\Throwable) {
            throw ValidationFailed::because('invalid_or_expired_token');
        }

        if (!is_array($outer) || !isset($outer['expiration_date'], $outer['value'])) {
            throw ValidationFailed::because('invalid_or_expired_token');
        }

        /** @var string $expirationDate */
        $expirationDate = $outer['expiration_date'];
        if (now()->isAfter(Carbon::parse($expirationDate))) {
            throw ValidationFailed::because('invalid_or_expired_token');
        }

        try {
            /** @var string $inner */
            $inner = Crypt::decrypt($outer['value']);
        } catch (\Throwable) {
            throw ValidationFailed::because('invalid_or_expired_token');
        }

        $parts = explode(';', $inner, 4);
        if (count($parts) < 3 || $parts[0] !== 'email_change') {
            throw ValidationFailed::because('invalid_or_expired_token');
        }

        return [
            'userId'   => (int) $parts[1],
            'newEmail' => $parts[2],
        ];
    }
}
