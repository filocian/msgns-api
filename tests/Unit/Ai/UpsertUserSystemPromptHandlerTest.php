<?php

declare(strict_types=1);

use Src\Ai\Application\Commands\UpsertUserSystemPrompt\UpsertUserSystemPromptCommand;
use Src\Ai\Application\Commands\UpsertUserSystemPrompt\UpsertUserSystemPromptHandler;
use Src\Ai\Domain\Entities\UserAiSystemPrompt;
use Src\Ai\Domain\Ports\UserAiSystemPromptRepository;
use Src\Ai\Domain\ValueObjects\AiProductType;

describe('UpsertUserSystemPromptHandler', function (): void {

    beforeEach(function (): void {
        $this->repository = Mockery::mock(UserAiSystemPromptRepository::class);
        $this->handler    = new UpsertUserSystemPromptHandler($this->repository);
    });

    afterEach(fn () => Mockery::close());

    it('saves a new prompt and returns the persisted entity', function (): void {
        $saved = UserAiSystemPrompt::create(1, AiProductType::GOOGLE_REVIEW, 'My prompt');

        $this->repository
            ->shouldReceive('save')
            ->once()
            ->andReturn($saved);

        $command = new UpsertUserSystemPromptCommand(
            userId: 1,
            productType: AiProductType::GOOGLE_REVIEW,
            promptText: 'My prompt',
        );

        $result = $this->handler->handle($command);

        expect($result)->toBeInstanceOf(UserAiSystemPrompt::class)
            ->and($result->productType)->toBe(AiProductType::GOOGLE_REVIEW)
            ->and($result->promptText)->toBe('My prompt');
    });

    it('calls repository save with a UserAiSystemPrompt built from command data', function (): void {
        $saved = UserAiSystemPrompt::create(2, AiProductType::INSTAGRAM_CONTENT, 'Instagram prompt');

        $this->repository
            ->shouldReceive('save')
            ->once()
            ->withArgs(function (UserAiSystemPrompt $prompt): bool {
                return $prompt->userId === 2
                    && $prompt->productType === AiProductType::INSTAGRAM_CONTENT
                    && $prompt->promptText === 'Instagram prompt';
            })
            ->andReturn($saved);

        $command = new UpsertUserSystemPromptCommand(
            userId: 2,
            productType: AiProductType::INSTAGRAM_CONTENT,
            promptText: 'Instagram prompt',
        );

        $this->handler->handle($command);
    });
});
