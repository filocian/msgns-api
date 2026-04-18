<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\ProductType;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Src\Ai\Domain\DataTransferObjects\AiResponse;
use Src\Ai\Domain\Ports\GeminiPort;
use Src\Ai\Domain\ValueObjects\AiProductType;
use Src\Ai\Domain\ValueObjects\AiResponseStatus;
use Database\Seeders\ProductConfigurationStatusSeeder;
use Src\Ai\Infrastructure\Persistence\AiResponseRecordModel;
use Src\Ai\Infrastructure\Persistence\AiUsageRecordModel;
use Src\Identity\Domain\Permissions\DomainPermissions;

function giveGenerateAiPermission(User $user): void
{
    Permission::findOrCreate(DomainPermissions::AI_FREE_PREVIEW, 'stateful-api');
    $user->givePermissionTo(DomainPermissions::AI_FREE_PREVIEW);
}

function mockGemini(string $content = 'AI reply generated'): void
{
    $gemini = Mockery::mock(GeminiPort::class);
    $gemini->shouldReceive('generate')->andReturn(new AiResponse($content, 1, 2, 3));
    app()->instance(GeminiPort::class, $gemini);
}

function makeOwnedProduct(int $userId): Product
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
        'password'             => 'pass-' . str()->random(6),
        'name'                 => 'Test Product',
        'description'          => 'desc',
        'active'               => true,
        'configuration_status' => 'not-started',
        'assigned_at'          => now(),
        'usage'                => 0,
    ]);
}

describe('POST /api/v2/ai/google/reviews/{reviewId}/generate', function (): void {

    beforeEach(fn () => $this->seed(ProductConfigurationStatusSeeder::class));

    it('returns 401 for unauthenticated requests', function (): void {
        $this->postJson('/api/v2/ai/google/reviews/rev-abc/generate', [])
            ->assertStatus(401);
    });

    it('returns 201 with AiResponseResource and persists a row on happy path', function (): void {
        $user = User::factory()->create(['email' => 'gen-happy@test.com']);
        giveGenerateAiPermission($user);

        $product = makeOwnedProduct($user->id);

        mockGemini('Thanks for the feedback!');

        $response = $this->actingAs($user, 'stateful-api')
            ->postJson('/api/v2/ai/google/reviews/rev-happy/generate', [
                'product_id'  => $product->id,
                'review_text' => 'Great product!',
                'star_rating' => 5,
            ])
            ->assertStatus(201);

        expect($response->json('data.id'))->toBeString()
            ->and($response->json('data.product_type'))->toBe(AiProductType::GOOGLE_REVIEW->value)
            ->and($response->json('data.status'))->toBe(AiResponseStatus::PENDING);

        $persisted = AiResponseRecordModel::where('user_id', $user->id)->firstOrFail();
        expect($persisted->metadata)->toMatchArray(['review_id' => 'rev-happy']);

        // BE-13 retrofit: AiUsageRecord is written after a successful Gemini call.
        $usage = AiUsageRecordModel::where('user_id', $user->id)->firstOrFail();
        expect($usage->product_type)->toBe('google_reviews')
            ->and($usage->source)->toBe('free')
            ->and($usage->tokens_used)->toBe(3);
    });

    it('returns 403 when the product does not belong to the authenticated user', function (): void {
        $user  = User::factory()->create(['email' => 'gen-403@test.com']);
        $other = User::factory()->create(['email' => 'gen-403-owner@test.com']);
        giveGenerateAiPermission($user);

        $product = makeOwnedProduct($other->id);

        mockGemini();

        $this->actingAs($user, 'stateful-api')
            ->postJson('/api/v2/ai/google/reviews/rev-bad/generate', [
                'product_id'  => $product->id,
                'review_text' => 'hmm',
                'star_rating' => 2,
            ])
            ->assertStatus(403);
    });

    it('returns 422 when an AiResponse for the same review_id already exists in pending status', function (): void {
        $user = User::factory()->create(['email' => 'gen-dup@test.com']);
        giveGenerateAiPermission($user);
        $product = makeOwnedProduct($user->id);

        AiResponseRecordModel::create([
            'user_id'                => $user->id,
            'product_type'           => AiProductType::GOOGLE_REVIEW->value,
            'product_id'             => $product->id,
            'ai_content'             => 'existing',
            'status'                 => AiResponseStatus::PENDING,
            'system_prompt_snapshot' => 'sp',
            'metadata'               => ['review_id' => 'rev-dup-http'],
            'expires_at'             => now()->addDays(5),
        ]);

        mockGemini();

        $this->actingAs($user, 'stateful-api')
            ->postJson('/api/v2/ai/google/reviews/rev-dup-http/generate', [
                'product_id'  => $product->id,
                'review_text' => 'text',
                'star_rating' => 3,
            ])
            ->assertStatus(422);
    });

    it('returns 422 when required body fields are missing', function (): void {
        $user = User::factory()->create(['email' => 'gen-422@test.com']);
        giveGenerateAiPermission($user);

        $this->actingAs($user, 'stateful-api')
            ->postJson('/api/v2/ai/google/reviews/rev-validate/generate', [])
            ->assertStatus(422);
    });
});
