<?php

declare(strict_types=1);

namespace Src\Ai\Domain\Ports;

use Src\Ai\Infrastructure\Persistence\AiResponseRecord;

interface AiResponseApplierPort
{
    public function supports(string $productType): bool;

    public function apply(AiResponseRecord $record): void;
}
