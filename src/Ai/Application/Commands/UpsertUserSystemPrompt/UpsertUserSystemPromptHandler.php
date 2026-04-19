<?php

declare(strict_types=1);

namespace Src\Ai\Application\Commands\UpsertUserSystemPrompt;

use Src\Ai\Domain\Entities\UserAiSystemPrompt;
use Src\Ai\Domain\Ports\UserAiSystemPromptRepository;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;

final class UpsertUserSystemPromptHandler implements CommandHandler
{
    public function __construct(
        private readonly UserAiSystemPromptRepository $repository,
    ) {}

    public function handle(Command $command): UserAiSystemPrompt
    {
        /** @var UpsertUserSystemPromptCommand $command */
        $prompt = UserAiSystemPrompt::create(
            userId: $command->userId,
            productType: $command->productType,
            promptText: $command->promptText,
        );

        return $this->repository->save($prompt);
    }
}
