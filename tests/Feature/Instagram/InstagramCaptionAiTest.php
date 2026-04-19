<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\ProductType;
use App\Models\User;
use Database\Seeders\ProductConfigurationStatusSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToWriteFile;
use Spatie\Permission\Models\Permission;
use Src\Ai\Domain\DataTransferObjects\AiResponse;
use Src\Ai\Domain\Ports\AiResponseApplierPort;
use Src\Ai\Domain\Ports\GeminiPort;
use Src\Ai\Domain\ValueObjects\AiProductType;
use Src\Ai\Domain\ValueObjects\AiResponseStatus;
use Src\Ai\Infrastructure\Persistence\AiResponseRecordModel;
use Src\Ai\Infrastructure\Persistence\AiUsageRecordModel;
use Src\Identity\Domain\Permissions\DomainPermissions;
use Src\Instagram\Application\Jobs\PublishInstagramContentJob;
use Src\Instagram\Infrastructure\Persistence\UserInstagramConnectionModel;
use Src\Shared\Core\Errors\MediaUploadFailed;
use Src\Shared\Core\Ports\LogPort;
use Src\Shared\Core\Ports\MediaUploadPort;

function igGiveAiPermission(User $user): void
{
    Permission::findOrCreate(DomainPermissions::AI_FREE_PREVIEW, 'stateful-api');
    $user->givePermissionTo(DomainPermissions::AI_FREE_PREVIEW);
}

function igMockGemini(string $content = 'Caption generated'): void
{
    $gemini = Mockery::mock(GeminiPort::class);
    $gemini->shouldReceive('generate')->andReturn(new AiResponse($content, 1, 2, 5));
    app()->instance(GeminiPort::class, $gemini);
}

function igMakeProductForUser(int $userId, ?string $instagramAccountId = null): Product
{
    $suffix = str()->lower(str()->random(8));
    $type   = ProductType::create([
        'code'          => 'nfc-card-' . $suffix,
        'name'          => 'NFC Card ' . $suffix,
        'description'   => 'Test type',
        'image_ref'     => (string) \Illuminate\Support\Str::uuid(),
        'primary_model' => 'nfc',
    ]);

    return Product::create([
        'product_type_id'      => $type->id,
        'user_id'              => $userId,
        'model'                => 'nfc',
        'linked_to_product_id' => null,
        'target_url'           => 'https://example.com',
        'instagram_account_id' => $instagramAccountId,
        'password'             => 'pass-' . str()->random(6),
        'name'                 => 'Test Product',
        'description'          => 'desc',
        'active'               => true,
        'configuration_status' => 'not-started',
        'assigned_at'          => now(),
        'usage'                => 0,
    ]);
}

function igMakeConnection(int $userId, string $accessToken = 'ig-token'): UserInstagramConnectionModel
{
    return UserInstagramConnectionModel::create([
        'user_id'            => $userId,
        'instagram_user_id'  => 'ig-user-999',
        'instagram_username' => 'testaccount',
        'page_id'            => 'page-456',
        'access_token'       => $accessToken,
        'expires_at'         => now()->addDays(30),
    ]);
}

// Base64 payload of a 1-byte PNG (not a real PNG, just some bytes for upload assertions)
$SMALL_B64 = base64_encode(hex2bin('89504e470d0a1a0a'));

describe('POST /api/v2/ai/instagram/generate', function () use ($SMALL_B64): void {

    beforeEach(fn () => $this->seed(ProductConfigurationStatusSeeder::class));

    it('returns 401 when unauthenticated', function (): void {
        $this->postJson('/api/v2/ai/instagram/generate', [])->assertStatus(401);
    });

    it('returns 422 when product_id is missing', function (): void {
        $user = User::factory()->create(['email' => 'ig-422-missing@test.com']);
        igGiveAiPermission($user);

        $this->actingAs($user, 'stateful-api')
            ->postJson('/api/v2/ai/instagram/generate', [])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error');
    });

    it('returns 422 when product_id is not found', function (): void {
        $user = User::factory()->create(['email' => 'ig-422-notfound@test.com']);
        igGiveAiPermission($user);

        $this->actingAs($user, 'stateful-api')
            ->postJson('/api/v2/ai/instagram/generate', ['product_id' => 999_999])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error');
    });

    it('returns 422 when image_base64 is provided without image_mime_type', function () use ($SMALL_B64): void {
        $user    = User::factory()->create(['email' => 'ig-422-mime-missing@test.com']);
        igGiveAiPermission($user);
        $product = igMakeProductForUser($user->id);

        $this->actingAs($user, 'stateful-api')
            ->postJson('/api/v2/ai/instagram/generate', [
                'product_id'   => $product->id,
                'image_base64' => $SMALL_B64,
            ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error');
    });

    it('returns 422 for an unsupported image_mime_type', function () use ($SMALL_B64): void {
        $user    = User::factory()->create(['email' => 'ig-422-mime-bad@test.com']);
        igGiveAiPermission($user);
        $product = igMakeProductForUser($user->id);

        $this->actingAs($user, 'stateful-api')
            ->postJson('/api/v2/ai/instagram/generate', [
                'product_id'      => $product->id,
                'image_base64'    => $SMALL_B64,
                'image_mime_type' => 'image/gif',
            ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error');
    });

    it('returns 403 when the product is owned by another user', function (): void {
        $user    = User::factory()->create(['email' => 'ig-403-caller@test.com']);
        $other   = User::factory()->create(['email' => 'ig-403-owner@test.com']);
        igGiveAiPermission($user);
        $product = igMakeProductForUser($other->id);

        igMockGemini();

        $this->actingAs($user, 'stateful-api')
            ->postJson('/api/v2/ai/instagram/generate', [
                'product_id' => $product->id,
                'context'    => 'hmm',
            ])
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'ai_response_forbidden');
    });

    it('returns 201 text-only and records usage without touching S3', function (): void {
        Storage::fake('s3');

        $user    = User::factory()->create(['email' => 'ig-201-textonly@test.com']);
        igGiveAiPermission($user);
        $product = igMakeProductForUser($user->id);

        igMockGemini('Text-only caption');

        $response = $this->actingAs($user, 'stateful-api')
            ->postJson('/api/v2/ai/instagram/generate', [
                'product_id' => $product->id,
                'context'    => 'Launching a new product!',
            ])
            ->assertStatus(201);

        expect($response->json('data.id'))->toBeString()
            ->and($response->json('data.product_type'))->toBe(AiProductType::INSTAGRAM_CONTENT->value)
            ->and($response->json('data.status'))->toBe(AiResponseStatus::PENDING)
            ->and($response->json('data.ai_content'))->toBe('Text-only caption')
            ->and($response->json('data.metadata.s3_image_url'))->toBeNull();

        $record = AiResponseRecordModel::where('user_id', $user->id)->firstOrFail();
        expect($record->product_type)->toBe(AiProductType::INSTAGRAM_CONTENT->value)
            ->and($record->metadata)->toBeArray()
            ->and(array_key_exists('s3_image_url', $record->metadata))->toBeTrue()
            ->and($record->metadata['s3_image_url'])->toBeNull();

        $usage = AiUsageRecordModel::where('user_id', $user->id)->firstOrFail();
        expect($usage->product_type)->toBe('instagram')
            ->and($usage->tokens_used)->toBe(5);

        // No files written to fake s3 disk
        expect(Storage::disk('s3')->allFiles())->toBe([]);
    });

    it('returns 201 when image_base64 is provided, uploads to S3 and persists metadata', function () use ($SMALL_B64): void {
        Storage::fake('s3');

        $user    = User::factory()->create(['email' => 'ig-201-withimage@test.com']);
        igGiveAiPermission($user);
        $product = igMakeProductForUser($user->id);

        igMockGemini('Image caption');

        $response = $this->actingAs($user, 'stateful-api')
            ->postJson('/api/v2/ai/instagram/generate', [
                'product_id'      => $product->id,
                'image_base64'    => $SMALL_B64,
                'image_mime_type' => 'image/jpeg',
                'context'         => 'Summer sale',
            ])
            ->assertStatus(201);

        expect($response->json('data.metadata.s3_image_url'))->toBeString()
            ->and($response->json('data.metadata.s3_image_url'))->not->toBe('');

        $record = AiResponseRecordModel::where('user_id', $user->id)->firstOrFail();
        expect($record->metadata['s3_image_url'] ?? null)->toBeString();

        $files = Storage::disk('s3')->allFiles();
        expect($files)->not->toBe([])
            ->and($files[0])->toStartWith('ai-media/' . $user->id . '/');

        $usage = AiUsageRecordModel::where('user_id', $user->id)->firstOrFail();
        expect($usage->product_type)->toBe('instagram');
    });

    it('returns 502 when the S3 upload fails', function () use ($SMALL_B64): void {
        $user    = User::factory()->create(['email' => 'ig-502-s3@test.com']);
        igGiveAiPermission($user);
        $product = igMakeProductForUser($user->id);

        igMockGemini();

        // Swap MediaUploadPort for a fake that throws the domain error.
        app()->instance(MediaUploadPort::class, new class implements MediaUploadPort {
            public function upload(string $base64Content, string $mimeType, int $userId): string
            {
                throw MediaUploadFailed::because('s3_upload_failed');
            }
        });

        $this->actingAs($user, 'stateful-api')
            ->postJson('/api/v2/ai/instagram/generate', [
                'product_id'      => $product->id,
                'image_base64'    => $SMALL_B64,
                'image_mime_type' => 'image/jpeg',
            ])
            ->assertStatus(502)
            ->assertJsonPath('error.code', 'media_upload_failed')
            ->assertJsonPath('error.context.reason', 's3_upload_failed');

        expect(AiResponseRecordModel::where('user_id', $user->id)->exists())->toBeFalse();
    });
});

describe('End-to-end generate -> approve -> apply', function () use ($SMALL_B64): void {

    beforeEach(fn () => $this->seed(ProductConfigurationStatusSeeder::class));

    it('enqueues publishing on apply and transitions to APPLIED when the job runs', function () use ($SMALL_B64): void {
        Storage::fake('s3');
        Queue::fake();

        $user    = User::factory()->create(['email' => 'ig-apply-happy@test.com']);
        igGiveAiPermission($user);
        $product = igMakeProductForUser($user->id, 'ig-biz-account-123');
        igMakeConnection($user->id);

        igMockGemini('Hello Instagram!');

        // Fake Graph API calls: create container, poll status, publish
        Http::fake([
            'graph.facebook.com/*/ig-biz-account-123/media'         => Http::response(['id' => 'creation-abc'], 200),
            'graph.facebook.com/*/creation-abc*'                    => Http::response(['status_code' => 'FINISHED'], 200),
            'graph.facebook.com/*/ig-biz-account-123/media_publish' => Http::response(['id' => 'media-xyz'], 200),
        ]);

        // 1. Generate
        $generated = $this->actingAs($user, 'stateful-api')
            ->postJson('/api/v2/ai/instagram/generate', [
                'product_id'      => $product->id,
                'image_base64'    => $SMALL_B64,
                'image_mime_type' => 'image/jpeg',
            ])
            ->assertStatus(201);

        $recordId = $generated->json('data.id');
        expect($recordId)->toBeString();

        // 2. Approve
        $this->actingAs($user, 'stateful-api')
            ->patchJson('/api/v2/ai/responses/' . $recordId . '/approve')
            ->assertStatus(204);

        // 3. Apply — response is immediate (204); job is queued, not yet run
        $this->actingAs($user, 'stateful-api')
            ->postJson('/api/v2/ai/responses/' . $recordId . '/apply')
            ->assertStatus(204);

        // After /apply returns, status is APPLYING and the job is queued.
        $record = AiResponseRecordModel::findOrFail($recordId);
        expect($record->status)->toBe(AiResponseStatus::APPLYING)
            ->and($record->applied_at)->toBeNull();

        Queue::assertPushed(
            PublishInstagramContentJob::class,
            fn (PublishInstagramContentJob $job): bool => $job->recordId === $recordId,
        );

        // 4. Run the job inline (simulates the worker picking it up)
        (new PublishInstagramContentJob($recordId))->handle(
            app(AiResponseApplierPort::class),
            app(LogPort::class),
        );

        // Verify terminal state after job execution
        $record->refresh();
        expect($record->status)->toBe(AiResponseStatus::APPLIED)
            ->and($record->applied_at)->not()->toBeNull();
    });
});
