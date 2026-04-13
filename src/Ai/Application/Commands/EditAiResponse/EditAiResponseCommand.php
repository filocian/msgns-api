<?php

declare(strict_types=1);

namespace Src\Ai\Application\Commands\EditAiResponse;

use Src\Shared\Core\Bus\Command;

final readonly class EditAiResponseCommand implements Command
{
    public function __construct(
        public string $id,
        public int $userId,
        public string $editedContent,
    ) {}

    public function commandName(): string
    {
        return 'ai.edit_ai_response';
    }
}
