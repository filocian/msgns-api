<?php

declare(strict_types=1);

use App\Models\User;

// ─── GET /api/v2/ai/system-prompts ────────────────────────────────────────────

describe('GET /api/v2/ai/system-prompts', function (): void {

    it('returns empty array when no prompts exist', function (): void {
        $user = $this->create_user(['email' => 'get-empty@test.com']);

        $this->actingAs($user, 'stateful-api')
            ->getJson('/api/v2/ai/system-prompts')
            ->assertStatus(200)
            ->assertJson(['data' => []]);
    });

    it('returns all prompts for authenticated user', function (): void {
        $user = $this->create_user(['email' => 'get-prompts@test.com']);

        $this->actingAs($user, 'stateful-api')
            ->putJson('/api/v2/ai/system-prompts/google_review', ['prompt_text' => 'Review prompt'])
            ->assertStatus(200);

        $this->actingAs($user, 'stateful-api')
            ->putJson('/api/v2/ai/system-prompts/instagram_content', ['prompt_text' => 'Instagram prompt'])
            ->assertStatus(200);

        $response = $this->actingAs($user, 'stateful-api')
            ->getJson('/api/v2/ai/system-prompts')
            ->assertStatus(200);

        $data = $response->json('data');
        expect(count($data))->toBe(2);

        $types = array_column($data, 'product_type');
        expect($types)->toContain('google_review')
            ->and($types)->toContain('instagram_content');
    });

    it('does not return prompts from other users', function (): void {
        $userA = $this->create_user(['email' => 'user-a@test.com']);
        $userB = $this->create_user(['email' => 'user-b@test.com']);

        $this->actingAs($userA, 'stateful-api')
            ->putJson('/api/v2/ai/system-prompts/google_review', ['prompt_text' => 'User A prompt'])
            ->assertStatus(200);

        $this->actingAs($userB, 'stateful-api')
            ->getJson('/api/v2/ai/system-prompts')
            ->assertStatus(200)
            ->assertJson(['data' => []]);
    });

    it('returns 401 for unauthenticated request', function (): void {
        $this->getJson('/api/v2/ai/system-prompts')
            ->assertStatus(401);
    });
});

// ─── PUT /api/v2/ai/system-prompts/{product_type} ────────────────────────────

describe('PUT /api/v2/ai/system-prompts/{product_type}', function (): void {

    it('creates a new prompt (upsert — create path)', function (): void {
        $user = $this->create_user(['email' => 'upsert-create@test.com']);

        $this->actingAs($user, 'stateful-api')
            ->putJson('/api/v2/ai/system-prompts/google_review', ['prompt_text' => 'My review prompt'])
            ->assertStatus(200)
            ->assertJsonPath('data.product_type', 'google_review')
            ->assertJsonPath('data.prompt_text', 'My review prompt');
    });

    it('updates an existing prompt (upsert — update path)', function (): void {
        $user = $this->create_user(['email' => 'upsert-update@test.com']);

        $this->actingAs($user, 'stateful-api')
            ->putJson('/api/v2/ai/system-prompts/google_review', ['prompt_text' => 'Original prompt'])
            ->assertStatus(200);

        $this->actingAs($user, 'stateful-api')
            ->putJson('/api/v2/ai/system-prompts/google_review', ['prompt_text' => 'Updated prompt'])
            ->assertStatus(200)
            ->assertJsonPath('data.product_type', 'google_review')
            ->assertJsonPath('data.prompt_text', 'Updated prompt');
    });

    it('returns 400 when prompt_text is missing', function (): void {
        $user = $this->create_user(['email' => 'upsert-missing@test.com']);

        $this->actingAs($user, 'stateful-api')
            ->putJson('/api/v2/ai/system-prompts/google_review', [])
            ->assertStatus(400);
    });

    it('returns 400 when prompt_text exceeds 1000 words', function (): void {
        $user = $this->create_user(['email' => 'upsert-long@test.com']);

        $longText = implode(' ', array_fill(0, 1001, 'word'));

        $this->actingAs($user, 'stateful-api')
            ->putJson('/api/v2/ai/system-prompts/google_review', ['prompt_text' => $longText])
            ->assertStatus(400);
    });

    it('returns 400 when product_type is invalid', function (): void {
        $user = $this->create_user(['email' => 'upsert-invalid@test.com']);

        $this->actingAs($user, 'stateful-api')
            ->putJson('/api/v2/ai/system-prompts/invalid_type', ['prompt_text' => 'Some prompt'])
            ->assertStatus(400);
    });

    it('returns 401 for unauthenticated request', function (): void {
        $this->putJson('/api/v2/ai/system-prompts/google_review', ['prompt_text' => 'prompt'])
            ->assertStatus(401);
    });
});

// ─── DELETE /api/v2/ai/system-prompts/{product_type} ─────────────────────────

describe('DELETE /api/v2/ai/system-prompts/{product_type}', function (): void {

    it('returns 204 and removes the prompt', function (): void {
        $user = $this->create_user(['email' => 'delete-success@test.com']);

        $this->actingAs($user, 'stateful-api')
            ->putJson('/api/v2/ai/system-prompts/google_review', ['prompt_text' => 'To be deleted'])
            ->assertStatus(200);

        $this->actingAs($user, 'stateful-api')
            ->deleteJson('/api/v2/ai/system-prompts/google_review')
            ->assertStatus(204);

        $this->actingAs($user, 'stateful-api')
            ->getJson('/api/v2/ai/system-prompts')
            ->assertStatus(200)
            ->assertJson(['data' => []]);
    });

    it('returns 404 when prompt does not exist', function (): void {
        $user = $this->create_user(['email' => 'delete-missing@test.com']);

        $this->actingAs($user, 'stateful-api')
            ->deleteJson('/api/v2/ai/system-prompts/google_review')
            ->assertStatus(404);
    });

    it('returns 400 when product_type is invalid', function (): void {
        $user = $this->create_user(['email' => 'delete-invalid@test.com']);

        $this->actingAs($user, 'stateful-api')
            ->deleteJson('/api/v2/ai/system-prompts/invalid_type')
            ->assertStatus(400);
    });

    it('returns 401 for unauthenticated request', function (): void {
        $this->deleteJson('/api/v2/ai/system-prompts/google_review')
            ->assertStatus(401);
    });
});
