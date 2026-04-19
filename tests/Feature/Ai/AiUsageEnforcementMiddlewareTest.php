<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Src\Ai\Infrastructure\Persistence\AiUsageRecordModel;
use Src\Identity\Domain\Permissions\DomainPermissions;

// ─── AiUsageEnforcementMiddleware feature tests ───────────────────────────────

describe('AiUsageEnforcementMiddleware', function (): void {

    beforeEach(function (): void {
        Permission::findOrCreate(DomainPermissions::AI_FREE_PREVIEW, 'stateful-api');

        Route::middleware(['auth:stateful-api', 'ai.enforce-usage:google_reviews'])
            ->get('/_test/ai/enforce/google-reviews', fn () => response()->json(['ok' => true], 200));
    });

    it('passes through when authenticated user has quota', function (): void {
        config(['services.gemini.ai_free_google_review_requests_per_month' => 5]);

        $user = User::factory()->create(['email' => 'enforce-pass@test.com']);
        $user->givePermissionTo(DomainPermissions::AI_FREE_PREVIEW);
        // No usage records — full quota available

        $this->actingAs($user, 'stateful-api')
            ->getJson('/_test/ai/enforce/google-reviews')
            ->assertStatus(200)
            ->assertJson(['ok' => true]);
    });

    it('returns 429 when quota is exhausted', function (): void {
        config(['services.gemini.ai_free_google_review_requests_per_month' => 5]);

        $user = User::factory()->create(['email' => 'enforce-429@test.com']);
        $user->givePermissionTo(DomainPermissions::AI_FREE_PREVIEW);

        // Exhaust the free quota
        for ($i = 0; $i < 5; $i++) {
            AiUsageRecordModel::query()->create([
                'user_id'      => $user->id,
                'source'       => 'free',
                'product_type' => 'google_reviews',
                'used_at'      => now(),
            ]);
        }

        $this->actingAs($user, 'stateful-api')
            ->getJson('/_test/ai/enforce/google-reviews')
            ->assertStatus(429)
            ->assertJson(['message' => 'AI quota exhausted.']);
    });

    it('returns 401 when request is unauthenticated', function (): void {
        $this->getJson('/_test/ai/enforce/google-reviews')
            ->assertStatus(401);
    });

    it('returns 403 when authenticated user has no AI permission', function (): void {
        $user = User::factory()->create(['email' => 'enforce-403@test.com']);
        // User exists but has no AI permission assigned

        $this->actingAs($user, 'stateful-api')
            ->getJson('/_test/ai/enforce/google-reviews')
            ->assertStatus(403)
            ->assertJson(['message' => 'Forbidden.']);
    });

});
