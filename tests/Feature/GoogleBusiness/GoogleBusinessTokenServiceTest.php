<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Src\GoogleBusiness\Domain\Models\UserGoogleBusinessConnection;
use Src\GoogleBusiness\Infrastructure\Adapters\GoogleBusinessApiAdapter;
use Src\GoogleBusiness\Infrastructure\Services\GoogleBusinessTokenService;

beforeEach(function (): void {
    config()->set('services.google.client_id', 'test-client-id');
    config()->set('services.google.client_secret', 'test-client-secret');
    config()->set('services.google.business_redirect_uri', 'http://localhost:8000/google-business/callback');
});

describe('GoogleBusinessTokenService', function (): void {

    it('returns existing token when not expired', function (): void {
        $user       = $this->create_user(['email' => 'token-valid@test.com']);
        $connection = UserGoogleBusinessConnection::create([
            'user_id'           => $user->id,
            'google_account_id' => 'google-123',
            'access_token'      => 'valid-access-token',
            'refresh_token'     => 'refresh-token',
            'token_expires_at'  => now()->addHour(), // expires in 60 minutes — well outside 5-minute buffer
        ]);

        Http::fake();

        $service = new GoogleBusinessTokenService(new GoogleBusinessApiAdapter());
        $result  = $service->ensureFreshToken($connection);

        expect($result->access_token)->toBe('valid-access-token');
        Http::assertNothingSent();
    });

    it('refreshes token when expired', function (): void {
        $user       = $this->create_user(['email' => 'token-expired@test.com']);
        $connection = UserGoogleBusinessConnection::create([
            'user_id'           => $user->id,
            'google_account_id' => 'google-123',
            'access_token'      => 'old-access-token',
            'refresh_token'     => 'my-refresh-token',
            'token_expires_at'  => now()->subMinutes(10), // already expired
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'refreshed-access-token',
                'expires_in'   => 3600,
            ], 200),
        ]);

        $service = new GoogleBusinessTokenService(new GoogleBusinessApiAdapter());
        $result  = $service->ensureFreshToken($connection);

        expect($result->access_token)->toBe('refreshed-access-token');
    });

    it('refreshes token when within 5 minute buffer', function (): void {
        $user       = $this->create_user(['email' => 'token-buffer@test.com']);
        $connection = UserGoogleBusinessConnection::create([
            'user_id'           => $user->id,
            'google_account_id' => 'google-123',
            'access_token'      => 'expiring-token',
            'refresh_token'     => 'my-refresh-token',
            'token_expires_at'  => now()->addMinutes(3), // within 5-minute buffer
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'fresh-access-token',
                'expires_in'   => 3600,
            ], 200),
        ]);

        $service = new GoogleBusinessTokenService(new GoogleBusinessApiAdapter());
        $result  = $service->ensureFreshToken($connection);

        expect($result->access_token)->toBe('fresh-access-token');
    });

    it('updates only access_token and token_expires_at after refresh — never overwrites refresh_token', function (): void {
        $user       = $this->create_user(['email' => 'token-nooverwrite@test.com']);
        $connection = UserGoogleBusinessConnection::create([
            'user_id'           => $user->id,
            'google_account_id' => 'google-123',
            'access_token'      => 'old-token',
            'refresh_token'     => 'original-refresh-token',
            'token_expires_at'  => now()->subMinutes(10),
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'new-token',
                'expires_in'   => 3600,
                // No new refresh_token — only access_token should be updated
            ], 200),
        ]);

        $service = new GoogleBusinessTokenService(new GoogleBusinessApiAdapter());
        $service->ensureFreshToken($connection);

        $connection->refresh();
        expect($connection->refresh_token)->toBe('original-refresh-token')
            ->and($connection->access_token)->toBe('new-token');
    });
});
