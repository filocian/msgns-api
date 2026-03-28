<?php

declare(strict_types=1);

namespace Src\Products\Domain\DataTransfer;

final readonly class GeneratedProductItem
{
    public function __construct(
        public int $id,
        public string $name,
        public string $password,
        public string $model,
        public string $redirectUrl,
    ) {}
}
