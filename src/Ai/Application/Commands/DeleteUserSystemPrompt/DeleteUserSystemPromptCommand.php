<?php

declare(strict_types=1);

namespace Src\Ai\Application\Commands\DeleteUserSystemPrompt;

use Src\Ai\Domain\ValueObjects\AiProductType;
use Src\Shared\Core\Bus\Command;

final readonly class DeleteUserSystemPromptCommand implements Command
{
    public function __construct(
        public int $userId,
        public AiProductType $productType,
    ) {}

    public function commandName(): string
    {
        return 'ai.delete_user_system_prompt';
    }
}
