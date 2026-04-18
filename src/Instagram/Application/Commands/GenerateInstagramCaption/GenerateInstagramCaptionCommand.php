<?php

declare(strict_types=1);

namespace Src\Instagram\Application\Commands\GenerateInstagramCaption;

use Src\Shared\Core\Bus\Command;

final readonly class GenerateInstagramCaptionCommand implements Command
{
    public function __construct(
        public int $userId,
        public int $productId,
        public ?string $imageBase64,
        public ?string $imageMimeType,
        public ?string $context,
    ) {}

    public function commandName(): string
    {
        return 'ai.generate_instagram_caption';
    }
}
