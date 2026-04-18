<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Instagram\Domain\DataTransferObjects\InstagramConnection;
use Src\Instagram\Domain\Models\UserInstagramConnection;
use Src\Instagram\Infrastructure\Adapters\EloquentInstagramConnectionRepository;

uses(RefreshDatabase::class);

describe('EloquentInstagramConnectionRepository', function (): void {

    it('returns a mapped InstagramConnection DTO when a row exists', function (): void {
        $user = User::factory()->create();

        $expiresAt = now()->addDays(30);

        UserInstagramConnection::create([
            'user_id'            => $user->id,
            'instagram_user_id'  => '17841400000000001',
            'instagram_username' => 'filocian_test',
            'page_id'            => '104000000000001',
            'access_token'       => 'EAAG_TEST_LONG_LIVED_TOKEN',
            'expires_at'         => $expiresAt,
        ]);

        $repo = new EloquentInstagramConnectionRepository();

        $connection = $repo->findByUserId($user->id);

        expect($connection)->toBeInstanceOf(InstagramConnection::class)
            ->and($connection->userId)->toBe($user->id)
            ->and($connection->accessToken)->toBe('EAAG_TEST_LONG_LIVED_TOKEN')
            ->and($connection->instagramUserId)->toBe('17841400000000001')
            ->and($connection->instagramUsername)->toBe('filocian_test')
            ->and($connection->pageId)->toBe('104000000000001')
            ->and($connection->expiresAt)->toBeInstanceOf(\DateTimeImmutable::class)
            ->and($connection->expiresAt->format('Y-m-d H:i:s'))->toBe($expiresAt->format('Y-m-d H:i:s'));

        // Execute isExpired against a real DTO — coverage for isExpired branch.
        expect($connection->isExpired())->toBeFalse();
    });

    it('returns null when no connection row exists for the user', function (): void {
        $repo = new EloquentInstagramConnectionRepository();

        expect($repo->findByUserId(99_999))->toBeNull();
    });

    it('still returns a DTO when the connection is expired — expiry is caller-owned', function (): void {
        $user = User::factory()->create();

        $pastExpiry = now()->subDays(1);

        UserInstagramConnection::create([
            'user_id'            => $user->id,
            'instagram_user_id'  => '17841400000000002',
            'instagram_username' => 'expired_user',
            'page_id'            => '104000000000002',
            'access_token'       => 'EAAG_EXPIRED_TOKEN',
            'expires_at'         => $pastExpiry,
        ]);

        $repo = new EloquentInstagramConnectionRepository();

        $connection = $repo->findByUserId($user->id);

        expect($connection)->toBeInstanceOf(InstagramConnection::class)
            ->and($connection->isExpired())->toBeTrue();
    });

    it('maps a null expires_at to a null DTO field and treats it as non-expiring', function (): void {
        $user = User::factory()->create();

        UserInstagramConnection::create([
            'user_id'            => $user->id,
            'instagram_user_id'  => '17841400000000003',
            'instagram_username' => 'forever_user',
            'page_id'            => '104000000000003',
            'access_token'       => 'EAAG_NO_EXPIRY_TOKEN',
            'expires_at'         => null,
        ]);

        $repo = new EloquentInstagramConnectionRepository();

        $connection = $repo->findByUserId($user->id);

        expect($connection)->toBeInstanceOf(InstagramConnection::class)
            ->and($connection->expiresAt)->toBeNull()
            ->and($connection->isExpired())->toBeFalse();
    });
});
