<?php

declare(strict_types=1);

use Src\Instagram\Infrastructure\Persistence\UserInstagramConnectionModel;

// ─── GET /api/v2/instagram/connection ─────────────────────────────────────────

describe('GET /api/v2/instagram/connection', function (): void {

    it('returns connected status when connection exists', function (): void {
        $user = $this->create_user(['email' => 'ig-conn-exists@test.com']);

        UserInstagramConnectionModel::create([
            'user_id'            => $user->id,
            'instagram_user_id'  => 'ig-user-123',
            'instagram_username' => 'mybusiness',
            'page_id'            => 'page-123',
            'access_token'       => 'some-access-token',
            'expires_at'         => now()->addDays(30),
        ]);

        $this->actingAs($user, 'stateful-api')
            ->getJson('/api/v2/instagram/connection')
            ->assertStatus(200)
            ->assertJsonPath('data.connected', true)
            ->assertJsonPath('data.instagram_username', 'mybusiness')
            ->assertJsonPath('data.expiring_soon', false);
    });

    it('returns expiring_soon true when token expires within 7 days', function (): void {
        $user = $this->create_user(['email' => 'ig-conn-expiring@test.com']);

        UserInstagramConnectionModel::create([
            'user_id'           => $user->id,
            'instagram_user_id' => 'ig-user-123',
            'access_token'      => 'some-access-token',
            'expires_at'        => now()->addDays(3),
        ]);

        $this->actingAs($user, 'stateful-api')
            ->getJson('/api/v2/instagram/connection')
            ->assertStatus(200)
            ->assertJsonPath('data.expiring_soon', true);
    });

    it('returns disconnected status when no connection', function (): void {
        $user = $this->create_user(['email' => 'ig-conn-none@test.com']);

        $this->actingAs($user, 'stateful-api')
            ->getJson('/api/v2/instagram/connection')
            ->assertStatus(200)
            ->assertJsonPath('data.connected', false)
            ->assertJsonPath('data.instagram_username', null)
            ->assertJsonPath('data.expires_at', null)
            ->assertJsonPath('data.expiring_soon', false);
    });

    it('requires authentication for connection status', function (): void {
        $this->getJson('/api/v2/instagram/connection')
            ->assertStatus(401);
    });
});

// ─── DELETE /api/v2/instagram/connection ──────────────────────────────────────

describe('DELETE /api/v2/instagram/connection', function (): void {

    it('disconnects and returns 204', function (): void {
        $user = $this->create_user(['email' => 'ig-disconnect@test.com']);

        UserInstagramConnectionModel::create([
            'user_id'           => $user->id,
            'instagram_user_id' => 'ig-user-123',
            'access_token'      => 'some-access-token',
            'expires_at'        => now()->addDays(30),
        ]);

        $this->actingAs($user, 'stateful-api')
            ->deleteJson('/api/v2/instagram/connection')
            ->assertStatus(204);

        expect(UserInstagramConnectionModel::where('user_id', $user->id)->exists())->toBeFalse();
    });

    it('returns 404 when no connection to disconnect', function (): void {
        $user = $this->create_user(['email' => 'ig-disconnect-none@test.com']);

        $this->actingAs($user, 'stateful-api')
            ->deleteJson('/api/v2/instagram/connection')
            ->assertStatus(404);
    });

    it('requires authentication for disconnect', function (): void {
        $this->deleteJson('/api/v2/instagram/connection')
            ->assertStatus(401);
    });
});
