<?php

declare(strict_types=1);

namespace Src\Ai\Application\Queries\GetUserSystemPrompts;

use Src\Shared\Core\Bus\Query;

final readonly class GetUserSystemPromptsQuery implements Query
{
    public function __construct(
        public int $userId,
    ) {}

    public function queryName(): string
    {
        return 'ai.get_user_system_prompts';
    }
}
