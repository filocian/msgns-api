<?php

declare(strict_types=1);

namespace Src\Ai\Domain\Errors;

use Src\Shared\Core\Errors\DomainException;

final class PackageInactiveException extends DomainException
{
    public static function withId(int $id): self
    {
        return new self('package_inactive', 422, ['package_id' => $id]);
    }
}
