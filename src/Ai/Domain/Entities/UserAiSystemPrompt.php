<?php

declare(strict_types=1);

namespace Src\Ai\Domain\Entities;

use Src\Ai\Domain\ValueObjects\AiProductType;

final class UserAiSystemPrompt
{
    private function __construct(
        public readonly int $userId,
        public readonly AiProductType $productType,
        public readonly string $promptText,
        public readonly \DateTimeImmutable $updatedAt,
    ) {}

    public static function create(int $userId, AiProductType $productType, string $promptText): self
    {
        return new self($userId, $productType, $promptText, new \DateTimeImmutable);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromPersistence(array $data): self
    {
        return new self(
            userId: (int) $data['user_id'],
            productType: AiProductType::from((string) $data['product_type']),
            promptText: (string) $data['prompt_text'],
            updatedAt: new \DateTimeImmutable((string) $data['updated_at']),
        );
    }

    public function applyUpdate(string $promptText): self
    {
        return new self($this->userId, $this->productType, $promptText, new \DateTimeImmutable);
    }
}
