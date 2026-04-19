<?php

declare(strict_types=1);

namespace Src\GoogleBusiness\Domain\Errors;

use Src\Shared\Core\Errors\DomainException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signals an OAuth flow failure (invalid state, denied consent, code exchange error).
 *
 * NOTE: This error is NEVER rendered by DomainExceptionHandler.
 * It is caught internally by GoogleBusinessCallbackController, which always
 * redirects the browser — never returns JSON or lets exceptions propagate.
 */
final class GoogleBusinessOAuthFailed extends DomainException
{
    public static function because(string $reason): self
    {
        return new self($reason, Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
