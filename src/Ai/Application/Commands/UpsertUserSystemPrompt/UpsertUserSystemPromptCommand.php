<?php

declare(strict_types=1);

namespace Src\Ai\Application\Commands\UpsertUserSystemPrompt;

use Src\Ai\Domain\ValueObjects\AiProductType;
use Src\Shared\Core\Bus\Command;

final readonly class UpsertUserSystemPromptCommand implements Command
{
    public function __construct(
        public int $userId,
        public AiProductType $productType,
        public string $promptText,
    ) {}

    public function commandName(): string
    {
        return 'ai.upsert_user_system_prompt';
    }
}
