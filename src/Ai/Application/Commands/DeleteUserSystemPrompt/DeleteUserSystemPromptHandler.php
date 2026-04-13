<?php

declare(strict_types=1);

namespace Src\Ai\Application\Commands\DeleteUserSystemPrompt;

use Src\Ai\Domain\Ports\UserAiSystemPromptRepository;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\NotFound;

final class DeleteUserSystemPromptHandler implements CommandHandler
{
    public function __construct(
        private readonly UserAiSystemPromptRepository $repository,
    ) {}

    public function handle(Command $command): null
    {
        /** @var DeleteUserSystemPromptCommand $command */
        $prompt = $this->repository->findByUserAndType($command->userId, $command->productType);

        if ($prompt === null) {
            throw NotFound::because('user_ai_system_prompt_not_found');
        }

        $this->repository->delete($command->userId, $command->productType);

        return null;
    }
}
