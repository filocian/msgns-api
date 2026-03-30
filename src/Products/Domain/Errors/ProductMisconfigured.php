<?php

declare(strict_types=1);

namespace Src\Products\Domain\Errors;

use Src\Shared\Core\Errors\DomainException;
use Symfony\Component\HttpFoundation\Response;

final class ProductMisconfigured extends DomainException
{
    public static function missingTargetUrl(int $id): self
    {
        return new self(
            'product_missing_target_url',
            Response::HTTP_UNPROCESSABLE_ENTITY,
            ['product_id' => $id],
        );
    }

    public static function notActive(int $id): self
    {
        return new self(
            'product_not_active',
            Response::HTTP_UNPROCESSABLE_ENTITY,
            ['product_id' => $id],
        );
    }

    public static function incompleteConfiguration(int $id): self
    {
        return new self(
            'product_incomplete_configuration',
            Response::HTTP_UNPROCESSABLE_ENTITY,
            ['product_id' => $id],
        );
    }
}
