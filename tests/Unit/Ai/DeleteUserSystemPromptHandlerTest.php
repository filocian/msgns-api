<?php

declare(strict_types=1);

use Src\Ai\Application\Commands\DeleteUserSystemPrompt\DeleteUserSystemPromptCommand;
use Src\Ai\Application\Commands\DeleteUserSystemPrompt\DeleteUserSystemPromptHandler;
use Src\Ai\Domain\Entities\UserAiSystemPrompt;
use Src\Ai\Domain\Ports\UserAiSystemPromptRepository;
use Src\Ai\Domain\ValueObjects\AiProductType;
use Src\Shared\Core\Errors\NotFound;

describe('DeleteUserSystemPromptHandler', function (): void {

    beforeEach(function (): void {
        $this->repository = Mockery::mock(UserAiSystemPromptRepository::class);
        $this->handler    = new DeleteUserSystemPromptHandler($this->repository);
    });

    afterEach(fn () => Mockery::close());

    it('deletes the prompt when it exists', function (): void {
        $prompt = UserAiSystemPrompt::create(1, AiProductType::GOOGLE_REVIEW, 'Existing prompt');

        $this->repository
            ->shouldReceive('findByUserAndType')
            ->once()
            ->with(1, AiProductType::GOOGLE_REVIEW)
            ->andReturn($prompt);

        $this->repository
            ->shouldReceive('delete')
            ->once()
            ->with(1, AiProductType::GOOGLE_REVIEW);

        $command = new DeleteUserSystemPromptCommand(
            userId: 1,
            productType: AiProductType::GOOGLE_REVIEW,
        );

        $result = $this->handler->handle($command);

        expect($result)->toBeNull();
    });

    it('throws NotFound when prompt does not exist', function (): void {
        $this->repository
            ->shouldReceive('findByUserAndType')
            ->once()
            ->with(1, AiProductType::GOOGLE_REVIEW)
            ->andReturn(null);

        $this->repository->shouldNotReceive('delete');

        $command = new DeleteUserSystemPromptCommand(
            userId: 1,
            productType: AiProductType::GOOGLE_REVIEW,
        );

        $this->handler->handle($command);
    })->throws(NotFound::class);

    it('does not call delete when prompt is not found', function (): void {
        $this->repository
            ->shouldReceive('findByUserAndType')
            ->once()
            ->andReturn(null);

        $this->repository->shouldNotReceive('delete');

        try {
            $this->handler->handle(new DeleteUserSystemPromptCommand(
                userId: 1,
                productType: AiProductType::GOOGLE_REVIEW,
            ));
        } catch (NotFound) {
            // Expected — verify delete was never called
        }
    });
});
