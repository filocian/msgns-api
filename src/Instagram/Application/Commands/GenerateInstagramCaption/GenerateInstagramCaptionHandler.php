<?php

declare(strict_types=1);

namespace Src\Instagram\Application\Commands\GenerateInstagramCaption;

use Src\Ai\Application\Services\AiUsageRecorder;
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
use Src\Shared\Core\Ports\MediaUploadPort;
use Src\Shared\Core\Ports\TransactionPort;

final class GenerateInstagramCaptionHandler implements CommandHandler
{
    private const string DEFAULT_SYSTEM_INSTRUCTION = 'You are a helpful assistant that writes engaging, concise Instagram captions for businesses. Write in a friendly, on-brand tone appropriate for the product and any context the user provides.';

    public function __construct(
        private readonly ProductRepositoryPort $productRepository,
        private readonly UserAiSystemPromptRepository $systemPromptRepository,
        private readonly GeminiPort $gemini,
        private readonly MediaUploadPort $mediaUpload,
        private readonly TransactionPort $transaction,
        private readonly AiUsageRecorder $usageRecorder,
    ) {}

    public function handle(Command $command): mixed
    {
        assert($command instanceof GenerateInstagramCaptionCommand);

        $product = $this->productRepository->findById($command->productId);

        if ($product === null || $product->userId !== $command->userId) {
            throw AiResponseForbidden::forUser($command->userId, $command->productId);
        }

        return $this->transaction->run(function () use ($command, $product): AiResponseRecordModel {
            $s3Url = null;

            if ($command->imageBase64 !== null && $command->imageMimeType !== null) {
                $s3Url = $this->mediaUpload->upload(
                    $command->imageBase64,
                    $command->imageMimeType,
                    $command->userId,
                );
            }

            $userSystemPrompt      = $this->systemPromptRepository->findByUserAndType($command->userId, AiProductType::INSTAGRAM_CONTENT);
            $systemInstructionText = $userSystemPrompt?->promptText ?? self::DEFAULT_SYSTEM_INSTRUCTION;

            $prompt = $this->composePrompt(
                productName: $product->name->value,
                context: $command->context,
            );

            $aiResponse = $this->gemini->generate(new AiRequest(
                prompt: $prompt,
                systemInstruction: $systemInstructionText,
                imageBase64: $command->imageBase64,
                imageMimeType: $command->imageMimeType,
            ));

            $record = AiResponseRecordModel::create([
                'user_id'                => $command->userId,
                'product_type'           => AiProductType::INSTAGRAM_CONTENT->value,
                'product_id'             => $command->productId,
                'ai_content'             => $aiResponse->content,
                'status'                 => AiResponseStatus::PENDING,
                'system_prompt_snapshot' => $systemInstructionText,
                'metadata'               => ['s3_image_url' => $s3Url],
            ]);

            $this->usageRecorder->record($command->userId, 'instagram', $aiResponse->totalTokens);

            return $record;
        });
    }

    private function composePrompt(string $productName, ?string $context): string
    {
        $base = sprintf(
            "Draft an engaging Instagram caption for the business \"%s\".",
            $productName,
        );

        if ($context !== null && $context !== '') {
            return $base . "\n\nAdditional context from the user: " . $context;
        }

        return $base;
    }
}
