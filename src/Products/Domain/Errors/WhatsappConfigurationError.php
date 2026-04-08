<?php

declare(strict_types=1);

namespace Src\Products\Domain\Errors;

use Src\Shared\Core\Errors\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class WhatsappConfigurationError extends DomainException
{
    public static function lastPhone(int $productId): self
    {
        return new self(
            errorCode: 'whatsapp_last_phone',
            httpStatus: Response::HTTP_CONFLICT,
            context: ['product_id' => $productId],
        );
    }

    public static function defaultMessageRemoval(int $messageId): self
    {
        return new self(
            errorCode: 'whatsapp_default_message_removal',
            httpStatus: Response::HTTP_CONFLICT,
            context: ['message_id' => $messageId],
        );
    }

    public static function duplicateLocale(int $phoneId, string $localeCode): self
    {
        return new self(
            errorCode: 'whatsapp_duplicate_locale',
            httpStatus: Response::HTTP_CONFLICT,
            context: ['phone_id' => $phoneId, 'locale_code' => $localeCode],
        );
    }
}
