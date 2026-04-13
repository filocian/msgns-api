<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Src\GoogleBusiness\Domain\Models\UserGoogleBusinessConnection;

// ─── GET /google-business/callback ────────────────────────────────────────────

describe('GET /google-business/callback', function (): void {

    beforeEach(function (): void {
        Config::set('services.google.client_id', 'test-client-id');
        Config::set('services.google.client_secret', 'test-client-secret');
        Config::set('services.google.business_redirect_uri', 'http://localhost/google-business/callback');
        Config::set('services.products.v2_front_url', 'https://app-v2.msgns.test');
    });

    it('exchanges code for tokens and creates connection', function (): void {
        $user = $this->create_user(['email' => 'callback-create@test.com']);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token'  => 'new-access-token',
                'refresh_token' => 'new-refresh-token',
                'expires_in'    => 3600,
            ], 200),
            'https://www.googleapis.com/oauth2/v3/userinfo' => Http::response([
                'sub'   => 'google-account-123',
                'email' => 'user@gmail.com',
            ], 200),
        ]);

        $this->actingAs($user, 'stateful-api')
            ->withSession(['google_business_oauth_state' => 'valid-state'])
            ->get('/google-business/callback?code=auth-code&state=valid-state')
            ->assertRedirect();

        $connection = UserGoogleBusinessConnection::where('user_id', $user->id)->first();
        expect($connection)->not->toBeNull()
            ->and($connection->google_account_id)->toBe('google-account-123');
    });

    it('updates existing connection on re-authorization', function (): void {
        $user = $this->create_user(['email' => 'callback-upsert@test.com']);

        // First connection
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token'  => 'first-access-token',
                'refresh_token' => 'first-refresh-token',
                'expires_in'    => 3600,
            ], 200),
            'https://www.googleapis.com/oauth2/v3/userinfo' => Http::response([
                'sub' => 'google-account-123',
            ], 200),
        ]);

        $this->actingAs($user, 'stateful-api')
            ->withSession(['google_business_oauth_state' => 'state-1'])
            ->get('/google-business/callback?code=code-1&state=state-1');

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token'  => 'second-access-token',
                'refresh_token' => 'second-refresh-token',
                'expires_in'    => 3600,
            ], 200),
            'https://www.googleapis.com/oauth2/v3/userinfo' => Http::response([
                'sub' => 'google-account-123',
            ], 200),
        ]);

        $this->actingAs($user, 'stateful-api')
            ->withSession(['google_business_oauth_state' => 'state-2'])
            ->get('/google-business/callback?code=code-2&state=state-2');

        expect(UserGoogleBusinessConnection::where('user_id', $user->id)->count())->toBe(1);
    });

    it('redirects to v2_front_url with connected=true on success', function (): void {
        $user = $this->create_user(['email' => 'callback-success@test.com']);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token'  => 'access-token',
                'refresh_token' => 'refresh-token',
                'expires_in'    => 3600,
            ], 200),
            'https://www.googleapis.com/oauth2/v3/userinfo' => Http::response([
                'sub' => 'google-account-456',
            ], 200),
        ]);

        $response = $this->actingAs($user, 'stateful-api')
            ->withSession(['google_business_oauth_state' => 'valid-state'])
            ->get('/google-business/callback?code=auth-code&state=valid-state');

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        expect($location)->toContain('app-v2.msgns.test')
            ->and($location)->toContain('connected=true');
    });

    it('rejects callback with mismatched state — redirects with error', function (): void {
        $user = $this->create_user(['email' => 'callback-mismatch@test.com']);

        $response = $this->actingAs($user, 'stateful-api')
            ->withSession(['google_business_oauth_state' => 'correct-state'])
            ->get('/google-business/callback?code=auth-code&state=wrong-state');

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        expect($location)->toContain('error=oauth_failed');
    });

    it('rejects callback with missing state — redirects with error', function (): void {
        $user = $this->create_user(['email' => 'callback-nostate@test.com']);

        $response = $this->actingAs($user, 'stateful-api')
            ->withSession(['google_business_oauth_state' => 'some-state'])
            ->get('/google-business/callback?code=auth-code');

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        expect($location)->toContain('error=oauth_failed');
    });

    it('redirects with error when user denies consent', function (): void {
        $user = $this->create_user(['email' => 'callback-denied@test.com']);

        $response = $this->actingAs($user, 'stateful-api')
            ->withSession(['google_business_oauth_state' => 'valid-state'])
            ->get('/google-business/callback?error=access_denied&state=valid-state');

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        expect($location)->toContain('error=oauth_failed');
    });

    it('does not redirect to front_url — always uses v2_front_url', function (): void {
        $user = $this->create_user(['email' => 'callback-v2url@test.com']);

        Config::set('services.products.front_url', 'https://old.msgns.test');
        Config::set('services.products.v2_front_url', 'https://app-v2.msgns.test');

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token'  => 'access-token',
                'refresh_token' => 'refresh-token',
                'expires_in'    => 3600,
            ], 200),
            'https://www.googleapis.com/oauth2/v3/userinfo' => Http::response([
                'sub' => 'google-account-789',
            ], 200),
        ]);

        $response = $this->actingAs($user, 'stateful-api')
            ->withSession(['google_business_oauth_state' => 'valid-state'])
            ->get('/google-business/callback?code=auth-code&state=valid-state');

        $location = $response->headers->get('Location');
        expect($location)->toContain('app-v2.msgns.test')
            ->and($location)->not->toContain('old.msgns.test');
    });

    it('does not overwrite refresh_token on re-authorization when google does not return one', function (): void {
        $user = $this->create_user(['email' => 'callback-norefresh@test.com']);

        // Initial authorization — creates connection with original refresh_token
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token'  => 'first-access-token',
                'refresh_token' => 'original-refresh-token',
                'expires_in'    => 3600,
            ], 200),
            'https://www.googleapis.com/oauth2/v3/userinfo' => Http::response([
                'sub' => 'google-account-123',
            ], 200),
        ]);

        $this->actingAs($user, 'stateful-api')
            ->withSession(['google_business_oauth_state' => 'state-1'])
            ->get('/google-business/callback?code=code-1&state=state-1');

        // Re-authorization — Google does NOT return a refresh_token this time
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'second-access-token',
                'expires_in'   => 3600,
                // No refresh_token in response
            ], 200),
            'https://www.googleapis.com/oauth2/v3/userinfo' => Http::response([
                'sub' => 'google-account-123',
            ], 200),
        ]);

        $this->actingAs($user, 'stateful-api')
            ->withSession(['google_business_oauth_state' => 'state-2'])
            ->get('/google-business/callback?code=code-2&state=state-2');

        $connection = UserGoogleBusinessConnection::where('user_id', $user->id)->first();
        expect($connection->refresh_token)->toBe('original-refresh-token');
    });

    it('redirects with error=session_expired when session has no authenticated user', function (): void {
        // No actingAs — no authenticated user in session
        $response = $this->withSession(['google_business_oauth_state' => 'valid-state'])
            ->get('/google-business/callback?code=auth-code&state=valid-state');

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        expect($location)->toContain('error=session_expired');
    });
});
