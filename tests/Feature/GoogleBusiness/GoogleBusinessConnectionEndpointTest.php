<?php

declare(strict_types=1);

use Src\GoogleBusiness\Domain\Models\UserGoogleBusinessConnection;

// ─── GET /api/v2/google-business/connection ───────────────────────────────────

describe('GET /api/v2/google-business/connection', function (): void {

    it('returns connected status with account details when connection exists', function (): void {
        $user = $this->create_user(['email' => 'connection-exists@test.com']);

        UserGoogleBusinessConnection::create([
            'user_id'             => $user->id,
            'google_account_id'   => 'google-account-abc',
            'business_location_id' => 'locations/123456',
            'business_name'        => 'My Test Business',
            'access_token'         => 'some-access-token',
            'refresh_token'        => 'some-refresh-token',
            'token_expires_at'     => now()->addHour(),
        ]);

        $this->actingAs($user, 'stateful-api')
            ->getJson('/api/v2/google-business/connection')
            ->assertStatus(200)
            ->assertJsonPath('data.connected', true)
            ->assertJsonPath('data.google_account_id', 'google-account-abc')
            ->assertJsonPath('data.business_location_id', 'locations/123456')
            ->assertJsonPath('data.business_name', 'My Test Business');
    });

    it('returns not connected status when no connection exists', function (): void {
        $user = $this->create_user(['email' => 'connection-none@test.com']);

        $this->actingAs($user, 'stateful-api')
            ->getJson('/api/v2/google-business/connection')
            ->assertStatus(200)
            ->assertJsonPath('data.connected', false)
            ->assertJsonPath('data.google_account_id', null)
            ->assertJsonPath('data.business_location_id', null)
            ->assertJsonPath('data.business_name', null);
    });

    it('returns 401 for unauthenticated requests on status endpoint', function (): void {
        $this->getJson('/api/v2/google-business/connection')
            ->assertStatus(401);
    });
});

// ─── DELETE /api/v2/google-business/connection ────────────────────────────────

describe('DELETE /api/v2/google-business/connection', function (): void {

    it('deletes connection and returns 204', function (): void {
        $user = $this->create_user(['email' => 'disconnect-success@test.com']);

        UserGoogleBusinessConnection::create([
            'user_id'           => $user->id,
            'google_account_id' => 'google-account-del',
            'access_token'      => 'access-token',
            'refresh_token'     => 'refresh-token',
            'token_expires_at'  => now()->addHour(),
        ]);

        $this->actingAs($user, 'stateful-api')
            ->deleteJson('/api/v2/google-business/connection')
            ->assertStatus(204);

        expect(UserGoogleBusinessConnection::where('user_id', $user->id)->exists())->toBeFalse();
    });

    it('returns 401 for unauthenticated requests on disconnect endpoint', function (): void {
        $this->deleteJson('/api/v2/google-business/connection')
            ->assertStatus(401);
    });
});
