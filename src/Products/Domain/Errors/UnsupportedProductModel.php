<?php

declare(strict_types=1);

namespace Src\Products\Domain\Errors;

use Src\Shared\Core\Errors\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class UnsupportedProductModel extends DomainException
{
    public static function forModel(string $model): self
    {
        return new self(
            errorCode: 'unsupported_product_model',
            httpStatus: Response::HTTP_UNPROCESSABLE_ENTITY,
            context: ['model' => $model],
        );
    }
}
