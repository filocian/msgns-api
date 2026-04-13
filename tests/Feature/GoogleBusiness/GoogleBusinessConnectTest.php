<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;

// ─── GET /google-business/connect ─────────────────────────────────────────────

describe('GET /google-business/connect', function (): void {

    beforeEach(function (): void {
        Config::set('services.google.client_id', 'test-client-id');
        Config::set('services.google.client_secret', 'test-client-secret');
        Config::set('services.google.business_redirect_uri', 'http://localhost/google-business/callback');
    });

    it('redirects authenticated user to google oauth url with correct params', function (): void {
        $user = $this->create_user(['email' => 'connect-redirect@test.com']);

        $response = $this->actingAs($user, 'stateful-api')
            ->get('/google-business/connect');

        $response->assertRedirect();

        $location = $response->headers->get('Location');
        expect($location)->toContain('accounts.google.com/o/oauth2/v2/auth')
            ->and($location)->toContain('client_id=test-client-id')
            ->and($location)->toContain(urlencode('http://localhost/google-business/callback'));
    });

    it('includes scope business.manage in oauth url', function (): void {
        $user = $this->create_user(['email' => 'connect-scope@test.com']);

        $response = $this->actingAs($user, 'stateful-api')
            ->get('/google-business/connect');

        $location = $response->headers->get('Location');
        expect($location)->toContain(urlencode('https://www.googleapis.com/auth/business.manage'));
    });

    it('includes access_type=offline and prompt=consent in oauth url', function (): void {
        $user = $this->create_user(['email' => 'connect-offline@test.com']);

        $response = $this->actingAs($user, 'stateful-api')
            ->get('/google-business/connect');

        $location = $response->headers->get('Location');
        expect($location)->toContain('access_type=offline')
            ->and($location)->toContain('prompt=consent');
    });

    it('stores state parameter in session before redirect', function (): void {
        $user = $this->create_user(['email' => 'connect-state@test.com']);

        $this->actingAs($user, 'stateful-api')
            ->get('/google-business/connect');

        $this->assertNotNull(session('google_business_oauth_state'));
        expect(strlen((string) session('google_business_oauth_state')))->toBeGreaterThan(0);
    });

    it('returns 401 for unauthenticated user', function (): void {
        $this->getJson('/google-business/connect')
            ->assertStatus(401);
    });
});
