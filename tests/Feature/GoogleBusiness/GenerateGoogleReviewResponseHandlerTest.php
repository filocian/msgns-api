<?php

declare(strict_types=1);

use App\Models\User;
use Src\Ai\Domain\DataTransferObjects\AiRequest;
use Src\Ai\Domain\DataTransferObjects\AiResponse;
use Src\Ai\Domain\Entities\UserAiSystemPrompt;
use Src\Ai\Domain\Errors\AiResponseForbidden;
use Src\Ai\Domain\Ports\GeminiPort;
use Src\Ai\Domain\Ports\UserAiSystemPromptRepository;
use Src\Ai\Domain\ValueObjects\AiProductType;
use Src\Ai\Domain\ValueObjects\AiResponseStatus;
use Src\Ai\Infrastructure\Persistence\AiResponseRecordModel;
use Src\GoogleBusiness\Application\Commands\GenerateGoogleReviewResponse\GenerateGoogleReviewResponseCommand;
use Src\GoogleBusiness\Application\Commands\GenerateGoogleReviewResponse\GenerateGoogleReviewResponseHandler;
use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Shared\Core\Errors\ValidationFailed;

afterEach(fn () => Mockery::close());

function makeReviewProduct(int $id, int $userId): Product
{
    return Product::fromPersistence(
        id: $id,
        productTypeId: 1,
        userId: $userId,
        model: 'MS8',
        linkedToProductId: null,
        password: 'password123',
        targetUrl: 'https://example.com',
        usage: 0,
        name: 'Test Product',
        description: null,
        active: true,
        configurationStatus: ConfigurationStatus::from(ConfigurationStatus::COMPLETED),
        assignedAt: new \DateTimeImmutable(),
        size: null,
        createdAt: new \DateTimeImmutable(),
        updatedAt: new \DateTimeImmutable(),
        deletedAt: null,
    );
}

describe('GenerateGoogleReviewResponseHandler', function (): void {

    it('creates an AiResponseRecordModel with metadata.review_id on happy path', function (): void {
        $user      = User::factory()->create();
        $userId    = $user->id;
        $productId = 42;
        $reviewId  = 'rev-abc';

        $productRepo = Mockery::mock(ProductRepositoryPort::class);
        $productRepo->shouldReceive('findById')->with($productId)->andReturn(makeReviewProduct($productId, $userId));

        $promptRepo = Mockery::mock(UserAiSystemPromptRepository::class);
        $promptRepo->shouldReceive('findByUserAndType')
            ->with($userId, AiProductType::GOOGLE_REVIEW)
            ->andReturn(UserAiSystemPrompt::create($userId, AiProductType::GOOGLE_REVIEW, 'Be helpful and polite.'));

        $gemini = Mockery::mock(GeminiPort::class);
        $gemini->shouldReceive('generate')
            ->once()
            ->withArgs(function (AiRequest $req) {
                return str_contains($req->prompt, 'Great service!')
                    && str_contains($req->prompt, '5')
                    && $req->systemInstruction === 'Be helpful and polite.';
            })
            ->andReturn(new AiResponse('Thank you for your kind review!', 10, 20, 30));

        $handler = new GenerateGoogleReviewResponseHandler($productRepo, $promptRepo, $gemini);

        $command = new GenerateGoogleReviewResponseCommand(
            userId: $userId,
            productId: $productId,
            reviewId: $reviewId,
            reviewText: 'Great service!',
            starRating: 5,
        );

        $result = $handler->handle($command);

        expect($result)->toBeInstanceOf(AiResponseRecordModel::class);

        /** @var AiResponseRecordModel $result */
        $persisted = AiResponseRecordModel::where('id', $result->id)->firstOrFail();

        expect($persisted->user_id)->toBe($userId)
            ->and($persisted->product_id)->toBe($productId)
            ->and($persisted->product_type)->toBe(AiProductType::GOOGLE_REVIEW->value)
            ->and($persisted->status)->toBe(AiResponseStatus::PENDING)
            ->and($persisted->ai_content)->toBe('Thank you for your kind review!')
            ->and($persisted->metadata)->toMatchArray(['review_id' => $reviewId]);
    });

    it('throws AiResponseForbidden when product belongs to a different user', function (): void {
        $user   = User::factory()->create();
        $userId = $user->id;

        $productRepo = Mockery::mock(ProductRepositoryPort::class);
        $productRepo->shouldReceive('findById')->with(99)->andReturn(makeReviewProduct(99, $userId + 1));

        $promptRepo = Mockery::mock(UserAiSystemPromptRepository::class);
        $gemini     = Mockery::mock(GeminiPort::class);
        $gemini->shouldNotReceive('generate');

        $handler = new GenerateGoogleReviewResponseHandler($productRepo, $promptRepo, $gemini);

        $command = new GenerateGoogleReviewResponseCommand(
            userId: $userId,
            productId: 99,
            reviewId: 'rev-x',
            reviewText: 'bad',
            starRating: 1,
        );

        expect(fn () => $handler->handle($command))->toThrow(AiResponseForbidden::class);
    });

    it('throws AiResponseForbidden when product does not exist', function (): void {
        $user   = User::factory()->create();
        $userId = $user->id;

        $productRepo = Mockery::mock(ProductRepositoryPort::class);
        $productRepo->shouldReceive('findById')->with(123)->andReturn(null);

        $promptRepo = Mockery::mock(UserAiSystemPromptRepository::class);
        $gemini     = Mockery::mock(GeminiPort::class);

        $handler = new GenerateGoogleReviewResponseHandler($productRepo, $promptRepo, $gemini);

        $command = new GenerateGoogleReviewResponseCommand(
            userId: $userId,
            productId: 123,
            reviewId: 'rev-x',
            reviewText: 'bad',
            starRating: 1,
        );

        expect(fn () => $handler->handle($command))->toThrow(AiResponseForbidden::class);
    });

    it('throws ValidationFailed when a pending AiResponse already exists for the review_id', function (): void {
        $user      = User::factory()->create();
        $userId    = $user->id;
        $productId = 42;
        $reviewId  = 'rev-dup';

        AiResponseRecordModel::create([
            'user_id'                => $userId,
            'product_type'           => AiProductType::GOOGLE_REVIEW->value,
            'product_id'             => $productId,
            'ai_content'             => 'existing content',
            'status'                 => AiResponseStatus::PENDING,
            'system_prompt_snapshot' => 'prompt',
            'metadata'               => ['review_id' => $reviewId],
            'expires_at'             => now()->addDays(5),
        ]);

        $productRepo = Mockery::mock(ProductRepositoryPort::class);
        $productRepo->shouldReceive('findById')->with($productId)->andReturn(makeReviewProduct($productId, $userId));

        $promptRepo = Mockery::mock(UserAiSystemPromptRepository::class);
        $promptRepo->shouldReceive('findByUserAndType')->andReturn(null);

        $gemini = Mockery::mock(GeminiPort::class);
        $gemini->shouldNotReceive('generate');

        $handler = new GenerateGoogleReviewResponseHandler($productRepo, $promptRepo, $gemini);

        $command = new GenerateGoogleReviewResponseCommand(
            userId: $userId,
            productId: $productId,
            reviewId: $reviewId,
            reviewText: 'text',
            starRating: 3,
        );

        expect(fn () => $handler->handle($command))->toThrow(ValidationFailed::class);
    });

    it('throws ValidationFailed when an approved AiResponse already exists for the review_id', function (): void {
        $user      = User::factory()->create();
        $userId    = $user->id;
        $productId = 42;
        $reviewId  = 'rev-dup-appr';

        AiResponseRecordModel::create([
            'user_id'                => $userId,
            'product_type'           => AiProductType::GOOGLE_REVIEW->value,
            'product_id'             => $productId,
            'ai_content'             => 'existing',
            'status'                 => AiResponseStatus::APPROVED,
            'system_prompt_snapshot' => 'prompt',
            'metadata'               => ['review_id' => $reviewId],
            'expires_at'             => now()->addDays(5),
        ]);

        $productRepo = Mockery::mock(ProductRepositoryPort::class);
        $productRepo->shouldReceive('findById')->with($productId)->andReturn(makeReviewProduct($productId, $userId));

        $promptRepo = Mockery::mock(UserAiSystemPromptRepository::class);
        $promptRepo->shouldReceive('findByUserAndType')->andReturn(null);

        $gemini = Mockery::mock(GeminiPort::class);
        $gemini->shouldNotReceive('generate');

        $handler = new GenerateGoogleReviewResponseHandler($productRepo, $promptRepo, $gemini);

        $command = new GenerateGoogleReviewResponseCommand(
            userId: $userId,
            productId: $productId,
            reviewId: $reviewId,
            reviewText: 'text',
            starRating: 3,
        );

        expect(fn () => $handler->handle($command))->toThrow(ValidationFailed::class);
    });

    it('allows creation when the existing record for the same review_id is rejected', function (): void {
        $user      = User::factory()->create();
        $userId    = $user->id;
        $productId = 42;
        $reviewId  = 'rev-reuse';

        AiResponseRecordModel::create([
            'user_id'                => $userId,
            'product_type'           => AiProductType::GOOGLE_REVIEW->value,
            'product_id'             => $productId,
            'ai_content'             => 'old rejected',
            'status'                 => AiResponseStatus::REJECTED,
            'system_prompt_snapshot' => 'prompt',
            'metadata'               => ['review_id' => $reviewId],
            'expires_at'             => now()->addDays(5),
        ]);

        $productRepo = Mockery::mock(ProductRepositoryPort::class);
        $productRepo->shouldReceive('findById')->with($productId)->andReturn(makeReviewProduct($productId, $userId));

        $promptRepo = Mockery::mock(UserAiSystemPromptRepository::class);
        $promptRepo->shouldReceive('findByUserAndType')->andReturn(null);

        $gemini = Mockery::mock(GeminiPort::class);
        $gemini->shouldReceive('generate')->andReturn(new AiResponse('new reply', 5, 10, 15));

        $handler = new GenerateGoogleReviewResponseHandler($productRepo, $promptRepo, $gemini);

        $command = new GenerateGoogleReviewResponseCommand(
            userId: $userId,
            productId: $productId,
            reviewId: $reviewId,
            reviewText: 'text',
            starRating: 3,
        );

        $result = $handler->handle($command);

        expect($result)->toBeInstanceOf(AiResponseRecordModel::class)
            ->and(AiResponseRecordModel::where('user_id', $userId)->count())->toBe(2);
    });
});
