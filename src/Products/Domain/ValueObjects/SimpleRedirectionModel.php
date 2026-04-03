<?php

declare(strict_types=1);

namespace Src\Products\Domain\ValueObjects;

enum SimpleRedirectionModel: string
{
    case GOOGLE = 'google';
    case INSTAGRAM = 'instagram';
    case YOUTUBE = 'youtube';
    case TIKTOK = 'tiktok';
    case FACEBOOK = 'facebook';
    case INFO = 'info';

    public static function supports(string $value): bool
    {
        return self::tryFrom($value) !== null;
    }
}
