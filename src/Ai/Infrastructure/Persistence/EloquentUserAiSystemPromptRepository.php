<?php

declare(strict_types=1);

namespace Src\Ai\Infrastructure\Persistence;

use Src\Ai\Domain\Entities\UserAiSystemPrompt;
use Src\Ai\Domain\Ports\UserAiSystemPromptRepository;
use Src\Ai\Domain\ValueObjects\AiProductType;

final class EloquentUserAiSystemPromptRepository implements UserAiSystemPromptRepository
{
    /**
     * @return UserAiSystemPrompt[]
     */
    public function findAllByUser(int $userId): array
    {
        return UserAiSystemPromptModel::where('user_id', $userId)
            ->get()
            ->map(fn (UserAiSystemPromptModel $m) => UserAiSystemPrompt::fromPersistence($m->toArray()))
            ->all();
    }

    public function findByUserAndType(int $userId, AiProductType $productType): ?UserAiSystemPrompt
    {
        $model = UserAiSystemPromptModel::where('user_id', $userId)
            ->where('product_type', $productType->value)
            ->first();

        return $model instanceof UserAiSystemPromptModel
            ? UserAiSystemPrompt::fromPersistence($model->toArray())
            : null;
    }

    public function save(UserAiSystemPrompt $prompt): UserAiSystemPrompt
    {
        $model = UserAiSystemPromptModel::updateOrCreate(
            ['user_id' => $prompt->userId, 'product_type' => $prompt->productType->value],
            ['prompt_text' => $prompt->promptText],
        );

        return UserAiSystemPrompt::fromPersistence($model->toArray());
    }

    public function delete(int $userId, AiProductType $productType): void
    {
        UserAiSystemPromptModel::where('user_id', $userId)
            ->where('product_type', $productType->value)
            ->delete();
    }
}
