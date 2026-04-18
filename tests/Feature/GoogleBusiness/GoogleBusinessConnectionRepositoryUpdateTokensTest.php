<?php

declare(strict_types=1);

use Src\GoogleBusiness\Domain\Models\UserGoogleBusinessConnection;
use Src\GoogleBusiness\Domain\Ports\GoogleBusinessConnectionRepositoryPort;

describe('GoogleBusinessConnectionRepository::updateTokens', function (): void {

    it('persists new access_token and updates token_expires_at to now + expiresIn seconds', function (): void {
        $user = $this->create_user(['email' => 'update-tokens@test.com']);

        UserGoogleBusinessConnection::create([
            'user_id'              => $user->id,
            'google_account_id'    => 'acc-xyz',
            'business_location_id' => 'loc-abc',
            'business_name'        => 'Biz',
            'access_token'         => 'old-access-token',
            'refresh_token'        => 'refresh-abc',
            'token_expires_at'     => now()->subHour(),
        ]);

        /** @var GoogleBusinessConnectionRepositoryPort $repo */
        $repo = app(GoogleBusinessConnectionRepositoryPort::class);

        $frozen = now();
        \Illuminate\Support\Carbon::setTestNow($frozen);

        $repo->updateTokens($user->id, 'new-access-token', 3600);

        $fresh = UserGoogleBusinessConnection::where('user_id', $user->id)->firstOrFail();

        expect($fresh->access_token)->toBe('new-access-token')
            ->and($fresh->token_expires_at->timestamp)->toBe($frozen->copy()->addSeconds(3600)->timestamp);

        \Illuminate\Support\Carbon::setTestNow();
    });

    it('is a no-op when no connection exists for the user', function (): void {
        $user = $this->create_user(['email' => 'no-conn@test.com']);

        /** @var GoogleBusinessConnectionRepositoryPort $repo */
        $repo = app(GoogleBusinessConnectionRepositoryPort::class);

        $repo->updateTokens($user->id, 'whatever', 3600);

        expect(UserGoogleBusinessConnection::where('user_id', $user->id)->exists())->toBeFalse();
    });
});
