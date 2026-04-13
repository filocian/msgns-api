<?php

declare(strict_types=1);

namespace Src\Ai\Domain\Services;

use Src\Ai\Domain\Ports\AiResponseApplierPort;
use Src\Ai\Infrastructure\Persistence\AiResponseRecord;
use Src\Shared\Core\Errors\NotFound;

final class CompositeAiResponseApplier implements AiResponseApplierPort
{
    /**
     * @param list<AiResponseApplierPort> $appliers
     */
    public function __construct(
        private readonly array $appliers,
    ) {}

    public function supports(string $productType): bool
    {
        foreach ($this->appliers as $applier) {
            if ($applier->supports($productType)) {
                return true;
            }
        }

        return false;
    }

    public function apply(AiResponseRecord $record): void
    {
        foreach ($this->appliers as $applier) {
            if ($applier->supports($record->product_type)) {
                $applier->apply($record);

                return;
            }
        }

        throw NotFound::entity('ai-response-applier', $record->product_type);
    }
}
