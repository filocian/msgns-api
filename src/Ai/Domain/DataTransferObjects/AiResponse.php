<?php

declare(strict_types=1);

namespace Src\Ai\Domain\DataTransferObjects;

final readonly class AiResponse
{
    public function __construct(
        public string $content,
        public int $promptTokens,
        public int $completionTokens,
        public int $totalTokens,
    ) {}
}
