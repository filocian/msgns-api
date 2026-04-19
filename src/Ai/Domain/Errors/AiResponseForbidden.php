<?php

declare(strict_types=1);

namespace Src\Ai\Domain\Errors;

use Src\Shared\Core\Errors\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class AiResponseForbidden extends DomainException
{
    public static function forUser(int $userId, int|string $contextId): self
    {
        return new self('ai_response_forbidden', Response::HTTP_FORBIDDEN, [
            'user_id'    => $userId,
            'context_id' => $contextId,
        ]);
    }
}
