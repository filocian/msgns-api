<?php

declare(strict_types=1);

namespace Src\Ai\Domain\DataTransferObjects;

final readonly class AiRequest
{
    public function __construct(
        public string $prompt,
        public string $systemInstruction,
        public ?string $imageBase64 = null,
        public ?string $imageMimeType = null,
    ) {}
}
