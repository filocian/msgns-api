<?php

declare(strict_types=1);

namespace Src\Ai\Application\Queries\GetUserSystemPrompts;

use Src\Ai\Domain\Entities\UserAiSystemPrompt;
use Src\Ai\Domain\Ports\UserAiSystemPromptRepository;
use Src\Shared\Core\Bus\Query;
use Src\Shared\Core\Bus\QueryHandler;

final class GetUserSystemPromptsHandler implements QueryHandler
{
    public function __construct(
        private readonly UserAiSystemPromptRepository $repository,
    ) {}

    /**
     * @return UserAiSystemPrompt[]
     */
    public function handle(Query $query): array
    {
        /** @var GetUserSystemPromptsQuery $query */
        return $this->repository->findAllByUser($query->userId);
    }
}
