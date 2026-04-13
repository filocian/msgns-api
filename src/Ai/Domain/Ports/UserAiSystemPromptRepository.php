<?php

declare(strict_types=1);

namespace Src\Ai\Domain\Ports;

use Src\Ai\Domain\Entities\UserAiSystemPrompt;
use Src\Ai\Domain\ValueObjects\AiProductType;

interface UserAiSystemPromptRepository
{
    /**
     * @return UserAiSystemPrompt[]
     */
    public function findAllByUser(int $userId): array;

    public function findByUserAndType(int $userId, AiProductType $productType): ?UserAiSystemPrompt;

    public function save(UserAiSystemPrompt $prompt): UserAiSystemPrompt;

    public function delete(int $userId, AiProductType $productType): void;
}
