<?php

declare(strict_types=1);

namespace Src\Ai\Application\Commands\ApplyAiResponse;

use Src\Shared\Core\Bus\Command;

final readonly class ApplyAiResponseCommand implements Command
{
    public function __construct(
        public string $id,
        public int $userId,
    ) {}

    public function commandName(): string
    {
        return 'ai.apply_ai_response';
    }
}
