<?php

declare(strict_types=1);

use App\Models\User;
use Src\Ai\Domain\Ports\AiResponseApplierPort;
use Src\Ai\Domain\ValueObjects\AiResponseStatus;
use Src\Ai\Infrastructure\Persistence\AiResponseRecord;
use Src\Shared\Core\Errors\NotFound;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function makeResponse(int $userId, string $status = 'pending', ?string $expiresAt = null): AiResponseRecord
{
    return AiResponseRecord::create([
        'user_id'               => $userId,
        'product_type'          => 'google_review',
        'product_id'            => 1,
        'ai_content'            => 'AI generated content',
        'status'                => $status,
        'system_prompt_snapshot' => 'System prompt text',
        'expires_at'            => $expiresAt ?? now()->addDays(5)->toDateTimeString(),
    ]);
}

// ─── GET /api/v2/ai/responses ─────────────────────────────────────────────────

describe('GET /api/v2/ai/responses', function (): void {

    it('returns 401 for unauthenticated request', function (): void {
        $this->getJson('/api/v2/ai/responses')
            ->assertStatus(401);
    });

    it('returns paginated list with meta', function (): void {
        $user = User::factory()->create();
        makeResponse($user->id);

        $response = $this->actingAs($user, 'stateful-api')
            ->getJson('/api/v2/ai/responses')
            ->assertStatus(200);

        expect($response->json('data.data'))->toHaveCount(1);
        expect($response->json('data.meta'))->toHaveKeys(['currentPage', 'lastPage', 'perPage', 'total']);
    });

    it('returns only the authenticated user responses', function (): void {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        makeResponse($userA->id);
        makeResponse($userB->id);

        $response = $this->actingAs($userA, 'stateful-api')
            ->getJson('/api/v2/ai/responses')
            ->assertStatus(200);

        expect($response->json('data.data'))->toHaveCount(1);
    });

    it('filters by status', function (): void {
        $user = User::factory()->create();
        makeResponse($user->id, 'pending');
        makeResponse($user->id, 'approved');

        $response = $this->actingAs($user, 'stateful-api')
            ->getJson('/api/v2/ai/responses?status=pending')
            ->assertStatus(200);

        expect($response->json('data.data'))->toHaveCount(1);
        expect($response->json('data.data.0.status'))->toBe('pending');
    });

    it('filters by product_type', function (): void {
        $user = User::factory()->create();
        makeResponse($user->id);

        AiResponseRecord::create([
            'user_id'               => $user->id,
            'product_type'          => 'instagram_content',
            'product_id'            => 2,
            'ai_content'            => 'Instagram content',
            'status'                => 'pending',
            'system_prompt_snapshot' => 'Prompt',
            'expires_at'            => now()->addDays(5),
        ]);

        $response = $this->actingAs($user, 'stateful-api')
            ->getJson('/api/v2/ai/responses?product_type=google_review')
            ->assertStatus(200);

        expect($response->json('data.data'))->toHaveCount(1);
        expect($response->json('data.data.0.product_type'))->toBe('google_review');
    });

    it('returns 400 for invalid status filter', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user, 'stateful-api')
            ->getJson('/api/v2/ai/responses?status=invalid_status')
            ->assertStatus(400);
    });
});

// ─── PATCH /api/v2/ai/responses/{id}/approve ──────────────────────────────────

describe('PATCH /api/v2/ai/responses/{id}/approve', function (): void {

    it('returns 401 for unauthenticated request', function (): void {
        $this->patchJson('/api/v2/ai/responses/00000000-0000-0000-0000-000000000001/approve')
            ->assertStatus(401);
    });

    it('transitions pending → approved', function (): void {
        $user = User::factory()->create();
        $record = makeResponse($user->id, 'pending');

        $this->actingAs($user, 'stateful-api')
            ->patchJson("/api/v2/ai/responses/{$record->id}/approve")
            ->assertStatus(204);

        expect($record->fresh()->status)->toBe('approved');
    });

    it('transitions edited → approved (re-approval after edit)', function (): void {
        $user = User::factory()->create();
        $record = makeResponse($user->id, 'edited');

        $this->actingAs($user, 'stateful-api')
            ->patchJson("/api/v2/ai/responses/{$record->id}/approve")
            ->assertStatus(204);

        expect($record->fresh()->status)->toBe('approved');
    });

    it('returns 422 for invalid transition (rejected → approved)', function (): void {
        $user = User::factory()->create();
        $record = makeResponse($user->id, 'rejected');

        $this->actingAs($user, 'stateful-api')
            ->patchJson("/api/v2/ai/responses/{$record->id}/approve")
            ->assertStatus(422);
    });

    it('returns 404 for non-existent UUID', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user, 'stateful-api')
            ->patchJson('/api/v2/ai/responses/00000000-0000-0000-0000-000000000099/approve')
            ->assertStatus(404);
    });
});

// ─── PATCH /api/v2/ai/responses/{id}/edit ─────────────────────────────────────

describe('PATCH /api/v2/ai/responses/{id}/edit', function (): void {

    it('returns 401 for unauthenticated request', function (): void {
        $this->patchJson('/api/v2/ai/responses/00000000-0000-0000-0000-000000000001/edit', [
            'edited_content' => 'New content',
        ])->assertStatus(401);
    });

    it('transitions pending → edited and stores edited_content', function (): void {
        $user = User::factory()->create();
        $record = makeResponse($user->id, 'pending');

        $this->actingAs($user, 'stateful-api')
            ->patchJson("/api/v2/ai/responses/{$record->id}/edit", [
                'edited_content' => 'My edited version',
            ])
            ->assertStatus(204);

        $record->refresh();
        expect($record->status)->toBe('edited');
        expect($record->edited_content)->toBe('My edited version');
    });

    it('re-edits an already-edited record without error (idempotency)', function (): void {
        $user = User::factory()->create();
        $record = makeResponse($user->id, 'edited');
        $record->edited_content = 'First edit';
        $record->save();

        $this->actingAs($user, 'stateful-api')
            ->patchJson("/api/v2/ai/responses/{$record->id}/edit", [
                'edited_content' => 'Second edit',
            ])
            ->assertStatus(204);

        expect($record->fresh()->edited_content)->toBe('Second edit');
        expect($record->fresh()->status)->toBe('edited');
    });

    it('returns 400 when edited_content is missing', function (): void {
        $user = User::factory()->create();
        $record = makeResponse($user->id, 'pending');

        $this->actingAs($user, 'stateful-api')
            ->patchJson("/api/v2/ai/responses/{$record->id}/edit", [])
            ->assertStatus(400);
    });

    it('returns 400 when edited_content is empty string', function (): void {
        $user = User::factory()->create();
        $record = makeResponse($user->id, 'pending');

        $this->actingAs($user, 'stateful-api')
            ->patchJson("/api/v2/ai/responses/{$record->id}/edit", [
                'edited_content' => '',
            ])
            ->assertStatus(400);
    });

    it('returns 422 for invalid transition (rejected → edited)', function (): void {
        $user = User::factory()->create();
        $record = makeResponse($user->id, 'rejected');

        $this->actingAs($user, 'stateful-api')
            ->patchJson("/api/v2/ai/responses/{$record->id}/edit", [
                'edited_content' => 'Try to edit rejected',
            ])
            ->assertStatus(422);
    });

    it('returns 404 for non-existent UUID', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user, 'stateful-api')
            ->patchJson('/api/v2/ai/responses/00000000-0000-0000-0000-000000000099/edit', [
                'edited_content' => 'content',
            ])
            ->assertStatus(404);
    });
});

// ─── PATCH /api/v2/ai/responses/{id}/reject ───────────────────────────────────

describe('PATCH /api/v2/ai/responses/{id}/reject', function (): void {

    it('returns 401 for unauthenticated request', function (): void {
        $this->patchJson('/api/v2/ai/responses/00000000-0000-0000-0000-000000000001/reject')
            ->assertStatus(401);
    });

    it('transitions pending → rejected', function (): void {
        $user = User::factory()->create();
        $record = makeResponse($user->id, 'pending');

        $this->actingAs($user, 'stateful-api')
            ->patchJson("/api/v2/ai/responses/{$record->id}/reject")
            ->assertStatus(204);

        expect($record->fresh()->status)->toBe('rejected');
    });

    it('transitions edited → rejected', function (): void {
        $user = User::factory()->create();
        $record = makeResponse($user->id, 'edited');

        $this->actingAs($user, 'stateful-api')
            ->patchJson("/api/v2/ai/responses/{$record->id}/reject")
            ->assertStatus(204);

        expect($record->fresh()->status)->toBe('rejected');
    });

    it('returns 422 for invalid transition (applied → rejected)', function (): void {
        $user = User::factory()->create();
        $record = makeResponse($user->id, 'applied');

        $this->actingAs($user, 'stateful-api')
            ->patchJson("/api/v2/ai/responses/{$record->id}/reject")
            ->assertStatus(422);
    });

    it('returns 404 for non-existent UUID', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user, 'stateful-api')
            ->patchJson('/api/v2/ai/responses/00000000-0000-0000-0000-000000000099/reject')
            ->assertStatus(404);
    });
});

// ─── POST /api/v2/ai/responses/{id}/apply ────────────────────────────────────

describe('POST /api/v2/ai/responses/{id}/apply', function (): void {

    it('returns 401 for unauthenticated request', function (): void {
        $this->postJson('/api/v2/ai/responses/00000000-0000-0000-0000-000000000001/apply')
            ->assertStatus(401);
    });

    it('transitions approved → applied, calls applier, sets applied_at', function (): void {
        $user = User::factory()->create();
        $record = makeResponse($user->id, 'approved');

        $mockApplier = Mockery::mock(AiResponseApplierPort::class);
        $mockApplier->shouldReceive('apply')->once();
        app()->instance(AiResponseApplierPort::class, $mockApplier);

        $this->actingAs($user, 'stateful-api')
            ->postJson("/api/v2/ai/responses/{$record->id}/apply")
            ->assertStatus(204);

        $record->refresh();
        expect($record->status)->toBe('applied');
        expect($record->applied_at)->not->toBeNull();
    });

    it('rolls back status when applier throws — response stays approved', function (): void {
        $user = User::factory()->create();
        $record = makeResponse($user->id, 'approved');

        $mockApplier = Mockery::mock(AiResponseApplierPort::class);
        $mockApplier->shouldReceive('apply')->andThrow(new RuntimeException('External API failed'));
        app()->instance(AiResponseApplierPort::class, $mockApplier);

        $this->actingAs($user, 'stateful-api')
            ->postJson("/api/v2/ai/responses/{$record->id}/apply")
            ->assertStatus(500);

        $record->refresh();
        expect($record->status)->toBe('approved');
        expect($record->applied_at)->toBeNull();
    });

    it('returns 404 when no applier matches product type', function (): void {
        $user = User::factory()->create();
        $record = makeResponse($user->id, 'approved');

        $mockApplier = Mockery::mock(AiResponseApplierPort::class);
        $mockApplier->shouldReceive('apply')->andThrow(NotFound::entity('ai-response-applier', 'google_review'));
        app()->instance(AiResponseApplierPort::class, $mockApplier);

        $this->actingAs($user, 'stateful-api')
            ->postJson("/api/v2/ai/responses/{$record->id}/apply")
            ->assertStatus(404);
    });

    it('returns 422 for invalid transition (pending → applied)', function (): void {
        $user = User::factory()->create();
        $record = makeResponse($user->id, 'pending');

        $this->actingAs($user, 'stateful-api')
            ->postJson("/api/v2/ai/responses/{$record->id}/apply")
            ->assertStatus(422);
    });

    it('returns 404 for non-existent UUID', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user, 'stateful-api')
            ->postJson('/api/v2/ai/responses/00000000-0000-0000-0000-000000000099/apply')
            ->assertStatus(404);
    });

    it('returns 422 for expired response', function (): void {
        $user = User::factory()->create();
        $record = makeResponse($user->id, 'expired');

        $this->actingAs($user, 'stateful-api')
            ->postJson("/api/v2/ai/responses/{$record->id}/apply")
            ->assertStatus(422);
    });
});
