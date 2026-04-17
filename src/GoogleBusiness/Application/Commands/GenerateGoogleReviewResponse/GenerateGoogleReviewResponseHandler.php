<?php

declare(strict_types=1);

namespace Src\GoogleBusiness\Application\Commands\GenerateGoogleReviewResponse;

use Src\Ai\Domain\DataTransferObjects\AiRequest;
use Src\Ai\Domain\Errors\AiResponseForbidden;
use Src\Ai\Domain\Ports\GeminiPort;
use Src\Ai\Domain\Ports\UserAiSystemPromptRepository;
use Src\Ai\Domain\ValueObjects\AiProductType;
use Src\Ai\Domain\ValueObjects\AiResponseStatus;
use Src\Ai\Infrastructure\Persistence\AiResponseRecordModel;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\ValidationFailed;

final class GenerateGoogleReviewResponseHandler implements CommandHandler
{
    private const string DEFAULT_SYSTEM_INSTRUCTION = 'You are a helpful assistant that drafts polite, concise, professional replies to customer reviews on Google Business.';

    public function __construct(
        private readonly ProductRepositoryPort $productRepository,
        private readonly UserAiSystemPromptRepository $systemPromptRepository,
        private readonly GeminiPort $gemini,
    ) {}

    public function handle(Command $command): mixed
    {
        assert($command instanceof GenerateGoogleReviewResponseCommand);

        $product = $this->productRepository->findById($command->productId);

        if ($product === null || $product->userId !== $command->userId) {
            throw AiResponseForbidden::forUser($command->userId, $command->productId);
        }

        $duplicate = AiResponseRecordModel::query()
            ->where('user_id', $command->userId)
            ->where('product_type', AiProductType::GOOGLE_REVIEW->value)
            ->where('metadata->review_id', $command->reviewId)
            ->whereIn('status', [AiResponseStatus::PENDING, AiResponseStatus::APPROVED])
            ->exists();

        if ($duplicate) {
            throw ValidationFailed::because('google_review_response_already_pending', [
                'review_id'  => $command->reviewId,
                'product_id' => $command->productId,
            ]);
        }

        $userSystemPrompt     = $this->systemPromptRepository->findByUserAndType($command->userId, AiProductType::GOOGLE_REVIEW);
        $systemInstructionText = $userSystemPrompt?->promptText ?? self::DEFAULT_SYSTEM_INSTRUCTION;

        $prompt = $this->composePrompt(
            reviewText: $command->reviewText,
            starRating: $command->starRating,
            productName: $product->name->value,
        );

        $aiResponse = $this->gemini->generate(new AiRequest(
            prompt: $prompt,
            systemInstruction: $systemInstructionText,
        ));

        return AiResponseRecordModel::create([
            'user_id'                => $command->userId,
            'product_type'           => AiProductType::GOOGLE_REVIEW->value,
            'product_id'             => $command->productId,
            'ai_content'             => $aiResponse->content,
            'status'                 => AiResponseStatus::PENDING,
            'system_prompt_snapshot' => $systemInstructionText,
            'metadata'               => ['review_id' => $command->reviewId],
        ]);
    }

    private function composePrompt(string $reviewText, int $starRating, string $productName): string
    {
        return sprintf(
            "Draft a reply to the following Google review for the business \"%s\".\n\nStar rating: %d/5\nReview text: %s\n\nWrite a reply that is polite, concise, and acknowledges the reviewer.",
            $productName,
            $starRating,
            $reviewText,
        );
    }
}
