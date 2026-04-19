<?php

declare(strict_types=1);

namespace Src\Ai\Application\Commands\RejectAiResponse;

use Src\Shared\Core\Bus\Command;

final readonly class RejectAiResponseCommand implements Command
{
    public function __construct(
        public string $id,
        public int $userId,
    ) {}

    public function commandName(): string
    {
        return 'ai.reject_ai_response';
    }
}
