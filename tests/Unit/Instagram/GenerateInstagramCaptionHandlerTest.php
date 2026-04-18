<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\ProductConfigurationStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Ai\Application\Services\AiUsageRecorder;
use Src\Ai\Domain\DataTransferObjects\AiRequest;
use Src\Ai\Domain\DataTransferObjects\AiResponse;
use Src\Ai\Domain\Entities\UserAiSystemPrompt;
use Src\Ai\Domain\Errors\AiResponseForbidden;
use Src\Ai\Domain\Ports\GeminiPort;
use Src\Ai\Domain\Ports\UserAiSystemPromptRepository;
use Src\Ai\Domain\ValueObjects\AiProductType;
use Src\Ai\Domain\ValueObjects\AiResponseStatus;
use Src\Ai\Infrastructure\Persistence\AiResponseRecordModel;
use Src\Instagram\Application\Commands\GenerateInstagramCaption\GenerateInstagramCaptionCommand;
use Src\Instagram\Application\Commands\GenerateInstagramCaption\GenerateInstagramCaptionHandler;
use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Shared\Core\Errors\MediaUploadFailed;
use Src\Shared\Core\Ports\MediaUploadPort;
use Src\Shared\Core\Ports\TransactionPort;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(ProductConfigurationStatusSeeder::class));

afterEach(fn () => Mockery::close());

function makeInstagramProduct(int $id, int $userId): Product
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
        name: 'Summer Collection',
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

/**
 * Build a TransactionPort mock that just invokes the callable (no actual DB tx).
 * Matches production semantics for the purposes of these unit tests where we
 * rely on RefreshDatabase rather than transactional rollback assertions.
 */
function makeTransactionPortPassthrough(): TransactionPort
{
    $tx = Mockery::mock(TransactionPort::class);
    $tx->shouldReceive('run')
        ->andReturnUsing(fn (callable $cb) => $cb());

    return $tx;
}

describe('GenerateInstagramCaptionHandler', function (): void {

    it('throws AiResponseForbidden when the product does not exist', function (): void {
        $user = User::factory()->create();

        $productRepo = Mockery::mock(ProductRepositoryPort::class);
        $productRepo->shouldReceive('findById')->with(1234)->andReturn(null);

        $promptRepo    = Mockery::mock(UserAiSystemPromptRepository::class);
        $gemini        = Mockery::mock(GeminiPort::class);
        $gemini->shouldNotReceive('generate');
        $mediaUpload   = Mockery::mock(MediaUploadPort::class);
        $mediaUpload->shouldNotReceive('upload');
        $tx            = makeTransactionPortPassthrough();
        $usageRecorder = Mockery::mock(AiUsageRecorder::class);
        $usageRecorder->shouldNotReceive('record');

        $handler = new GenerateInstagramCaptionHandler(
            $productRepo, $promptRepo, $gemini, $mediaUpload, $tx, $usageRecorder,
        );

        $command = new GenerateInstagramCaptionCommand(
            userId: $user->id,
            productId: 1234,
            imageBase64: null,
            imageMimeType: null,
            context: null,
        );

        try {
            $handler->handle($command);
            expect(true)->toBeFalse('Expected AiResponseForbidden');
        } catch (AiResponseForbidden $e) {
            expect($e->context()['user_id'])->toBe($user->id)
                ->and($e->context()['context_id'])->toBe(1234);
        }
    });

    it('throws AiResponseForbidden when the product belongs to another user', function (): void {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        $productRepo = Mockery::mock(ProductRepositoryPort::class);
        $productRepo->shouldReceive('findById')->with(99)->andReturn(makeInstagramProduct(99, $other->id));

        $promptRepo    = Mockery::mock(UserAiSystemPromptRepository::class);
        $gemini        = Mockery::mock(GeminiPort::class);
        $gemini->shouldNotReceive('generate');
        $mediaUpload   = Mockery::mock(MediaUploadPort::class);
        $mediaUpload->shouldNotReceive('upload');
        $tx            = makeTransactionPortPassthrough();
        $usageRecorder = Mockery::mock(AiUsageRecorder::class);
        $usageRecorder->shouldNotReceive('record');

        $handler = new GenerateInstagramCaptionHandler(
            $productRepo, $promptRepo, $gemini, $mediaUpload, $tx, $usageRecorder,
        );

        $command = new GenerateInstagramCaptionCommand(
            userId: $user->id,
            productId: 99,
            imageBase64: null,
            imageMimeType: null,
            context: null,
        );

        expect(fn () => $handler->handle($command))->toThrow(AiResponseForbidden::class);
    });

    it('runs the text-only flow: no media upload, metadata.s3_image_url is null, usage recorded with product_type=instagram', function (): void {
        $user      = User::factory()->create();
        $userId    = $user->id;
        $productId = 42;

        $productRepo = Mockery::mock(ProductRepositoryPort::class);
        $productRepo->shouldReceive('findById')->with($productId)->andReturn(makeInstagramProduct($productId, $userId));

        $promptRepo = Mockery::mock(UserAiSystemPromptRepository::class);
        $promptRepo->shouldReceive('findByUserAndType')
            ->with($userId, AiProductType::INSTAGRAM_CONTENT)
            ->andReturn(null);

        $gemini = Mockery::mock(GeminiPort::class);
        $gemini->shouldReceive('generate')
            ->once()
            ->withArgs(function (AiRequest $req) {
                return $req->imageBase64 === null
                    && $req->imageMimeType === null
                    && str_contains($req->prompt, 'Summer Collection');
            })
            ->andReturn(new AiResponse('Check out our Summer Collection!', 10, 20, 30));

        $mediaUpload = Mockery::mock(MediaUploadPort::class);
        $mediaUpload->shouldNotReceive('upload');

        $tx = makeTransactionPortPassthrough();

        $usageRecorder = Mockery::mock(AiUsageRecorder::class);
        $usageRecorder->shouldReceive('record')
            ->once()
            ->with($userId, 'instagram', 30);

        $handler = new GenerateInstagramCaptionHandler(
            $productRepo, $promptRepo, $gemini, $mediaUpload, $tx, $usageRecorder,
        );

        $command = new GenerateInstagramCaptionCommand(
            userId: $userId,
            productId: $productId,
            imageBase64: null,
            imageMimeType: null,
            context: 'Text-only post',
        );

        $result = $handler->handle($command);

        expect($result)->toBeInstanceOf(AiResponseRecordModel::class);

        /** @var AiResponseRecordModel $result */
        $persisted = AiResponseRecordModel::where('id', $result->id)->firstOrFail();

        expect($persisted->user_id)->toBe($userId)
            ->and($persisted->product_id)->toBe($productId)
            ->and($persisted->product_type)->toBe(AiProductType::INSTAGRAM_CONTENT->value)
            ->and($persisted->status)->toBe(AiResponseStatus::PENDING)
            ->and($persisted->ai_content)->toBe('Check out our Summer Collection!')
            ->and($persisted->metadata)->toMatchArray(['s3_image_url' => null]);
    });

    it('runs the with-image flow: uploads to S3, forwards base64 to Gemini, and persists metadata.s3_image_url', function (): void {
        $user      = User::factory()->create();
        $userId    = $user->id;
        $productId = 43;
        $base64    = base64_encode('fake-image-bytes');
        $mime      = 'image/jpeg';
        $stubUrl   = 'https://s3.example.com/ai-media/' . $userId . '/2026/04/18/abcd.jpg';

        $productRepo = Mockery::mock(ProductRepositoryPort::class);
        $productRepo->shouldReceive('findById')->with($productId)->andReturn(makeInstagramProduct($productId, $userId));

        $promptRepo = Mockery::mock(UserAiSystemPromptRepository::class);
        $promptRepo->shouldReceive('findByUserAndType')->andReturn(null);

        $gemini = Mockery::mock(GeminiPort::class);
        $gemini->shouldReceive('generate')
            ->once()
            ->withArgs(function (AiRequest $req) use ($base64, $mime) {
                return $req->imageBase64 === $base64
                    && $req->imageMimeType === $mime;
            })
            ->andReturn(new AiResponse('Captioned with image', 5, 10, 15));

        $mediaUpload = Mockery::mock(MediaUploadPort::class);
        $mediaUpload->shouldReceive('upload')
            ->once()
            ->with($base64, $mime, $userId)
            ->andReturn($stubUrl);

        $tx = makeTransactionPortPassthrough();

        $usageRecorder = Mockery::mock(AiUsageRecorder::class);
        $usageRecorder->shouldReceive('record')
            ->once()
            ->with($userId, 'instagram', 15);

        $handler = new GenerateInstagramCaptionHandler(
            $productRepo, $promptRepo, $gemini, $mediaUpload, $tx, $usageRecorder,
        );

        $command = new GenerateInstagramCaptionCommand(
            userId: $userId,
            productId: $productId,
            imageBase64: $base64,
            imageMimeType: $mime,
            context: null,
        );

        $result = $handler->handle($command);

        /** @var AiResponseRecordModel $result */
        expect($result->metadata)->toMatchArray(['s3_image_url' => $stubUrl]);
    });

    it('propagates MediaUploadFailed from the media port and does not call Gemini or persist anything', function (): void {
        $user      = User::factory()->create();
        $userId    = $user->id;
        $productId = 44;

        $productRepo = Mockery::mock(ProductRepositoryPort::class);
        $productRepo->shouldReceive('findById')->with($productId)->andReturn(makeInstagramProduct($productId, $userId));

        $promptRepo = Mockery::mock(UserAiSystemPromptRepository::class);

        $gemini = Mockery::mock(GeminiPort::class);
        $gemini->shouldNotReceive('generate');

        $mediaUpload = Mockery::mock(MediaUploadPort::class);
        $mediaUpload->shouldReceive('upload')
            ->once()
            ->andThrow(MediaUploadFailed::because('s3_upload_failed'));

        $tx = makeTransactionPortPassthrough();

        $usageRecorder = Mockery::mock(AiUsageRecorder::class);
        $usageRecorder->shouldNotReceive('record');

        $handler = new GenerateInstagramCaptionHandler(
            $productRepo, $promptRepo, $gemini, $mediaUpload, $tx, $usageRecorder,
        );

        $command = new GenerateInstagramCaptionCommand(
            userId: $userId,
            productId: $productId,
            imageBase64: base64_encode('bytes'),
            imageMimeType: 'image/jpeg',
            context: null,
        );

        expect(fn () => $handler->handle($command))->toThrow(MediaUploadFailed::class);

        expect(AiResponseRecordModel::query()->count())->toBe(0);
    });

    it('propagates GeminiPort failures and does not persist an AiResponse or record usage', function (): void {
        $user      = User::factory()->create();
        $userId    = $user->id;
        $productId = 45;

        $productRepo = Mockery::mock(ProductRepositoryPort::class);
        $productRepo->shouldReceive('findById')->with($productId)->andReturn(makeInstagramProduct($productId, $userId));

        $promptRepo = Mockery::mock(UserAiSystemPromptRepository::class);
        $promptRepo->shouldReceive('findByUserAndType')->andReturn(null);

        $gemini = Mockery::mock(GeminiPort::class);
        $gemini->shouldReceive('generate')->once()->andThrow(new \RuntimeException('gemini boom'));

        $mediaUpload = Mockery::mock(MediaUploadPort::class);
        $mediaUpload->shouldNotReceive('upload');

        $tx = makeTransactionPortPassthrough();

        $usageRecorder = Mockery::mock(AiUsageRecorder::class);
        $usageRecorder->shouldNotReceive('record');

        $handler = new GenerateInstagramCaptionHandler(
            $productRepo, $promptRepo, $gemini, $mediaUpload, $tx, $usageRecorder,
        );

        $command = new GenerateInstagramCaptionCommand(
            userId: $userId,
            productId: $productId,
            imageBase64: null,
            imageMimeType: null,
            context: null,
        );

        expect(fn () => $handler->handle($command))->toThrow(\RuntimeException::class);
        expect(AiResponseRecordModel::query()->count())->toBe(0);
    });

    it('invokes TransactionPort::run with a closure that drives the DB writes', function (): void {
        $user      = User::factory()->create();
        $userId    = $user->id;
        $productId = 46;

        $productRepo = Mockery::mock(ProductRepositoryPort::class);
        $productRepo->shouldReceive('findById')->with($productId)->andReturn(makeInstagramProduct($productId, $userId));

        $promptRepo = Mockery::mock(UserAiSystemPromptRepository::class);
        $promptRepo->shouldReceive('findByUserAndType')->andReturn(null);

        $gemini = Mockery::mock(GeminiPort::class);
        $gemini->shouldReceive('generate')->andReturn(new AiResponse('ok', 1, 2, 3));

        $mediaUpload = Mockery::mock(MediaUploadPort::class);

        $tx = Mockery::mock(TransactionPort::class);
        $tx->shouldReceive('run')
            ->once()
            ->withArgs(fn (callable $cb): bool => true)
            ->andReturnUsing(fn (callable $cb) => $cb());

        $usageRecorder = Mockery::mock(AiUsageRecorder::class);
        $usageRecorder->shouldReceive('record')->once();

        $handler = new GenerateInstagramCaptionHandler(
            $productRepo, $promptRepo, $gemini, $mediaUpload, $tx, $usageRecorder,
        );

        $command = new GenerateInstagramCaptionCommand(
            userId: $userId,
            productId: $productId,
            imageBase64: null,
            imageMimeType: null,
            context: null,
        );

        $handler->handle($command);
    });

    it('uses the custom user system prompt when one exists', function (): void {
        $user      = User::factory()->create();
        $userId    = $user->id;
        $productId = 47;

        $productRepo = Mockery::mock(ProductRepositoryPort::class);
        $productRepo->shouldReceive('findById')->with($productId)->andReturn(makeInstagramProduct($productId, $userId));

        $customPromptText = 'Write captions as a playful brand voice.';

        $promptRepo = Mockery::mock(UserAiSystemPromptRepository::class);
        $promptRepo->shouldReceive('findByUserAndType')
            ->with($userId, AiProductType::INSTAGRAM_CONTENT)
            ->andReturn(UserAiSystemPrompt::create($userId, AiProductType::INSTAGRAM_CONTENT, $customPromptText));

        $gemini = Mockery::mock(GeminiPort::class);
        $gemini->shouldReceive('generate')
            ->once()
            ->withArgs(fn (AiRequest $req) => $req->systemInstruction === $customPromptText)
            ->andReturn(new AiResponse('ok', 1, 2, 3));

        $mediaUpload = Mockery::mock(MediaUploadPort::class);

        $tx = makeTransactionPortPassthrough();

        $usageRecorder = Mockery::mock(AiUsageRecorder::class);
        $usageRecorder->shouldReceive('record');

        $handler = new GenerateInstagramCaptionHandler(
            $productRepo, $promptRepo, $gemini, $mediaUpload, $tx, $usageRecorder,
        );

        $command = new GenerateInstagramCaptionCommand(
            userId: $userId,
            productId: $productId,
            imageBase64: null,
            imageMimeType: null,
            context: null,
        );

        $result = $handler->handle($command);

        /** @var AiResponseRecordModel $result */
        expect($result->system_prompt_snapshot)->toBe($customPromptText);
    });

    it('uses the default Instagram system prompt when the user has none', function (): void {
        $user      = User::factory()->create();
        $userId    = $user->id;
        $productId = 48;

        $productRepo = Mockery::mock(ProductRepositoryPort::class);
        $productRepo->shouldReceive('findById')->with($productId)->andReturn(makeInstagramProduct($productId, $userId));

        $promptRepo = Mockery::mock(UserAiSystemPromptRepository::class);
        $promptRepo->shouldReceive('findByUserAndType')->andReturn(null);

        $gemini = Mockery::mock(GeminiPort::class);
        $gemini->shouldReceive('generate')
            ->once()
            ->withArgs(fn (AiRequest $req) => $req->systemInstruction !== ''
                && str_contains(strtolower($req->systemInstruction), 'instagram')
                && str_contains($req->systemInstruction, '2200'))
            ->andReturn(new AiResponse('ok', 1, 2, 3));

        $mediaUpload = Mockery::mock(MediaUploadPort::class);

        $tx = makeTransactionPortPassthrough();

        $usageRecorder = Mockery::mock(AiUsageRecorder::class);
        $usageRecorder->shouldReceive('record');

        $handler = new GenerateInstagramCaptionHandler(
            $productRepo, $promptRepo, $gemini, $mediaUpload, $tx, $usageRecorder,
        );

        $command = new GenerateInstagramCaptionCommand(
            userId: $userId,
            productId: $productId,
            imageBase64: null,
            imageMimeType: null,
            context: null,
        );

        $result = $handler->handle($command);

        /** @var AiResponseRecordModel $result */
        expect($result->system_prompt_snapshot)->not()->toBe(''); // default was used
    });
});
