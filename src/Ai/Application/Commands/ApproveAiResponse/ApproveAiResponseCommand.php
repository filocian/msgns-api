<?php

declare(strict_types=1);

namespace Src\Ai\Application\Commands\ApproveAiResponse;

use Src\Shared\Core\Bus\Command;

final readonly class ApproveAiResponseCommand implements Command
{
    public function __construct(
        public string $id,
        public int $userId,
    ) {}

    public function commandName(): string
    {
        return 'ai.approve_ai_response';
    }
}
