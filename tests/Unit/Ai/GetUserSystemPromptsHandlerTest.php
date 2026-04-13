<?php

declare(strict_types=1);

use Src\Ai\Application\Queries\GetUserSystemPrompts\GetUserSystemPromptsHandler;
use Src\Ai\Application\Queries\GetUserSystemPrompts\GetUserSystemPromptsQuery;
use Src\Ai\Domain\Entities\UserAiSystemPrompt;
use Src\Ai\Domain\Ports\UserAiSystemPromptRepository;
use Src\Ai\Domain\ValueObjects\AiProductType;

describe('GetUserSystemPromptsHandler', function (): void {

    beforeEach(function (): void {
        $this->repository = Mockery::mock(UserAiSystemPromptRepository::class);
        $this->handler    = new GetUserSystemPromptsHandler($this->repository);
    });

    afterEach(fn () => Mockery::close());

    it('returns empty array when user has no prompts', function (): void {
        $this->repository
            ->shouldReceive('findAllByUser')
            ->once()
            ->with(1)
            ->andReturn([]);

        $result = $this->handler->handle(new GetUserSystemPromptsQuery(userId: 1));

        expect($result)->toBeArray()->toBeEmpty();
    });

    it('returns all prompts for the user', function (): void {
        $prompts = [
            UserAiSystemPrompt::create(1, AiProductType::GOOGLE_REVIEW, 'Review prompt'),
            UserAiSystemPrompt::create(1, AiProductType::INSTAGRAM_CONTENT, 'Instagram prompt'),
        ];

        $this->repository
            ->shouldReceive('findAllByUser')
            ->once()
            ->with(1)
            ->andReturn($prompts);

        $result = $this->handler->handle(new GetUserSystemPromptsQuery(userId: 1));

        expect($result)->toHaveCount(2)
            ->and($result[0]->productType)->toBe(AiProductType::GOOGLE_REVIEW)
            ->and($result[1]->productType)->toBe(AiProductType::INSTAGRAM_CONTENT);
    });

    it('calls repository with correct userId', function (): void {
        $this->repository
            ->shouldReceive('findAllByUser')
            ->once()
            ->with(42)
            ->andReturn([]);

        $this->handler->handle(new GetUserSystemPromptsQuery(userId: 42));
    });
});
