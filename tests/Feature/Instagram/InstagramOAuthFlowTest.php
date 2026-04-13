<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Src\Instagram\Domain\Models\UserInstagramConnection;

// ─── GET /instagram/connect ───────────────────────────────────────────────────

describe('GET /instagram/connect', function (): void {

    beforeEach(function (): void {
        Config::set('services.meta.app_id', 'test-app-id');
        Config::set('services.meta.app_secret', 'test-app-secret');
        Config::set('services.meta.redirect_uri', 'http://localhost/instagram/callback');
    });

    it('redirects to meta oauth dialog with correct params', function (): void {
        $user = $this->create_user(['email' => 'ig-connect-redirect@test.com']);

        $response = $this->actingAs($user, 'stateful-api')
            ->get('/instagram/connect');

        $response->assertRedirect();

        $location = $response->headers->get('Location');
        expect($location)->toContain('facebook.com')
            ->and($location)->toContain('client_id=test-app-id')
            ->and($location)->toContain(urlencode('http://localhost/instagram/callback'))
            ->and($location)->toContain('instagram_basic')
            ->and($location)->toContain('state=');
    });

    it('requires authentication to connect', function (): void {
        $this->getJson('/instagram/connect')
            ->assertStatus(401);
    });

    it('stores state in session before redirect', function (): void {
        $user = $this->create_user(['email' => 'ig-connect-state@test.com']);

        $this->actingAs($user, 'stateful-api')
            ->get('/instagram/connect');

        $this->assertNotNull(session('instagram_oauth_state'));
        expect(strlen((string) session('instagram_oauth_state')))->toBeGreaterThan(0);
    });
});

// ─── GET /instagram/callback ──────────────────────────────────────────────────

describe('GET /instagram/callback', function (): void {

    beforeEach(function (): void {
        Config::set('services.meta.app_id', 'test-app-id');
        Config::set('services.meta.app_secret', 'test-app-secret');
        Config::set('services.meta.redirect_uri', 'http://localhost/instagram/callback');
        Config::set('services.products.v2_front_url', 'https://app-v2.msgns.test');
    });

    it('exchanges code for long lived token and stores connection', function (): void {
        $user = $this->create_user(['email' => 'ig-callback-create@test.com']);

        Http::fake([
            'https://graph.facebook.com/*/oauth/access_token' => Http::response([
                'access_token' => 'short-lived-token',
                'token_type'   => 'bearer',
                'expires_in'   => 3600,
            ], 200),
            'https://graph.facebook.com/oauth/access_token*' => Http::response([
                'access_token' => 'long-lived-token',
                'token_type'   => 'bearer',
                'expires_in'   => 5184000,
            ], 200),
            'https://graph.facebook.com/*/me/accounts*' => Http::response([
                'data' => [['id' => 'page-123', 'name' => 'Test Page']],
            ], 200),
            'https://graph.facebook.com/*/page-123*' => Http::response([
                'instagram_business_account' => ['id' => 'ig-user-456'],
            ], 200),
            'https://graph.facebook.com/*/ig-user-456*' => Http::response([
                'username' => 'testbusiness',
            ], 200),
        ]);

        $this->actingAs($user, 'stateful-api')
            ->withSession(['instagram_oauth_state' => 'valid-state'])
            ->get('/instagram/callback?code=auth-code&state=valid-state')
            ->assertRedirect();

        $connection = UserInstagramConnection::where('user_id', $user->id)->first();
        expect($connection)->not->toBeNull()
            ->and($connection->instagram_user_id)->toBe('ig-user-456');
    });

    it('redirects to frontend with error on invalid state', function (): void {
        $user = $this->create_user(['email' => 'ig-callback-state@test.com']);

        $response = $this->actingAs($user, 'stateful-api')
            ->withSession(['instagram_oauth_state' => 'correct-state'])
            ->get('/instagram/callback?code=auth-code&state=wrong-state');

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        expect($location)->toContain('error=state_mismatch');
    });

    it('redirects to frontend with error on missing code', function (): void {
        $user = $this->create_user(['email' => 'ig-callback-nocode@test.com']);

        $response = $this->actingAs($user, 'stateful-api')
            ->withSession(['instagram_oauth_state' => 'valid-state'])
            ->get('/instagram/callback?state=valid-state');

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        expect($location)->toContain('error=missing_code');
    });

    it('updates existing connection on reconnect', function (): void {
        $user = $this->create_user(['email' => 'ig-callback-reconnect@test.com']);

        UserInstagramConnection::create([
            'user_id'           => $user->id,
            'instagram_user_id' => 'ig-user-456',
            'access_token'      => 'old-token',
            'expires_at'        => now()->addDays(30),
        ]);

        Http::fake([
            'https://graph.facebook.com/*/oauth/access_token' => Http::response([
                'access_token' => 'new-short-token',
                'expires_in'   => 3600,
            ], 200),
            'https://graph.facebook.com/oauth/access_token*' => Http::response([
                'access_token' => 'new-long-token',
                'expires_in'   => 5184000,
            ], 200),
            'https://graph.facebook.com/*/me/accounts*' => Http::response([
                'data' => [['id' => 'page-123']],
            ], 200),
            'https://graph.facebook.com/*/page-123*' => Http::response([
                'instagram_business_account' => ['id' => 'ig-user-456'],
            ], 200),
            'https://graph.facebook.com/*/ig-user-456*' => Http::response([
                'username' => 'testbusiness',
            ], 200),
        ]);

        $this->actingAs($user, 'stateful-api')
            ->withSession(['instagram_oauth_state' => 'state-2'])
            ->get('/instagram/callback?code=code-2&state=state-2');

        expect(UserInstagramConnection::where('user_id', $user->id)->count())->toBe(1);
    });

    it('redirects to v2 frontend after successful callback', function (): void {
        $user = $this->create_user(['email' => 'ig-callback-success@test.com']);

        Http::fake([
            'https://graph.facebook.com/*/oauth/access_token' => Http::response([
                'access_token' => 'short-token',
                'expires_in'   => 3600,
            ], 200),
            'https://graph.facebook.com/oauth/access_token*' => Http::response([
                'access_token' => 'long-token',
                'expires_in'   => 5184000,
            ], 200),
            'https://graph.facebook.com/*/me/accounts*' => Http::response([
                'data' => [['id' => 'page-1']],
            ], 200),
            'https://graph.facebook.com/*/page-1*' => Http::response([
                'instagram_business_account' => ['id' => 'ig-1'],
            ], 200),
            'https://graph.facebook.com/*/ig-1*' => Http::response([
                'username' => 'mybiz',
            ], 200),
        ]);

        $response = $this->actingAs($user, 'stateful-api')
            ->withSession(['instagram_oauth_state' => 'state'])
            ->get('/instagram/callback?code=code&state=state');

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        expect($location)->toContain('app-v2.msgns.test')
            ->and($location)->toContain('connected=true');
    });

    it('redirects to frontend with error when no business account found', function (): void {
        $user = $this->create_user(['email' => 'ig-callback-nobiz@test.com']);

        Http::fake([
            'https://graph.facebook.com/*/oauth/access_token' => Http::response([
                'access_token' => 'short-token',
                'expires_in'   => 3600,
            ], 200),
            'https://graph.facebook.com/oauth/access_token*' => Http::response([
                'access_token' => 'long-token',
                'expires_in'   => 5184000,
            ], 200),
            'https://graph.facebook.com/*/me/accounts*' => Http::response([
                'data' => [],
            ], 200),
        ]);

        $response = $this->actingAs($user, 'stateful-api')
            ->withSession(['instagram_oauth_state' => 'state'])
            ->get('/instagram/callback?code=code&state=state');

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        expect($location)->toContain('error=no_business_account');
    });

    it('redirects to frontend with error on graph api failure during callback', function (): void {
        $user = $this->create_user(['email' => 'ig-callback-apifail@test.com']);

        Http::fake([
            'https://graph.facebook.com/*' => Http::response([], 500),
        ]);

        $response = $this->actingAs($user, 'stateful-api')
            ->withSession(['instagram_oauth_state' => 'state'])
            ->get('/instagram/callback?code=code&state=state');

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        expect($location)->toContain('error=token_exchange_failed');
    });

    it('redirects with error when session has no authenticated user', function (): void {
        $response = $this->withSession(['instagram_oauth_state' => 'valid-state'])
            ->get('/instagram/callback?code=auth-code&state=valid-state');

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        expect($location)->toContain('error=unauthenticated');
    });
});
