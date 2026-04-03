<?php

declare(strict_types=1);

namespace Src\Products\Domain\Errors;

use Src\Shared\Core\Errors\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class InvalidConfigurationTransition extends DomainException
{
    public static function missingTargetUrl(int $productId): self
    {
        return new self(
            errorCode: 'missing_target_url',
            httpStatus: Response::HTTP_UNPROCESSABLE_ENTITY,
            context: ['product_id' => $productId],
        );
    }

    public static function cannotComplete(int $productId, string $currentStatus): self
    {
        return new self(
            errorCode: 'cannot_complete_configuration',
            httpStatus: Response::HTTP_UNPROCESSABLE_ENTITY,
            context: [
                'product_id' => $productId,
                'current_status' => $currentStatus,
            ],
        );
    }
}
