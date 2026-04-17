<?php

declare(strict_types=1);

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Src\GoogleBusiness\Domain\Models\UserGoogleBusinessConnection;
use Src\GoogleBusiness\Domain\Ports\GoogleBusinessApiPort;
use Src\Identity\Domain\Permissions\DomainPermissions;

function giveAiPermission(User $user): void
{
    Permission::findOrCreate(DomainPermissions::AI_FREE_PREVIEW, 'stateful-api');
    $user->givePermissionTo(DomainPermissions::AI_FREE_PREVIEW);
}

describe('GET /api/v2/ai/google/reviews', function (): void {

    it('returns 401 for unauthenticated requests', function (): void {
        $this->getJson('/api/v2/ai/google/reviews')
            ->assertStatus(401);
    });

    it('returns 404 when the user has no Google Business connection', function (): void {
        $user = User::factory()->create(['email' => 'no-conn-reviews@test.com']);
        giveAiPermission($user);

        $this->actingAs($user, 'stateful-api')
            ->getJson('/api/v2/ai/google/reviews')
            ->assertStatus(404);
    });

    it('returns 200 with list of reviews on happy path', function (): void {
        $user = User::factory()->create(['email' => 'list-reviews@test.com']);
        giveAiPermission($user);

        UserGoogleBusinessConnection::create([
            'user_id'              => $user->id,
            'google_account_id'    => 'acc-777',
            'business_location_id' => 'loc-123',
            'business_name'        => 'Biz',
            'access_token'         => 'tok-current',
            'refresh_token'        => 'ref-xyz',
            'token_expires_at'     => now()->addHour(),
        ]);

        $api = Mockery::mock(GoogleBusinessApiPort::class);
        $api->shouldReceive('fetchPendingReviews')
            ->once()
            ->with('tok-current', 'acc-777', 'loc-123')
            ->andReturn([
                ['reviewId' => 'rev-1', 'reviewer' => ['displayName' => 'Jane'], 'comment' => 'Nice!', 'starRating' => 'FIVE', 'createTime' => '2026-04-10T10:00:00Z'],
            ]);
        $api->shouldNotReceive('refreshAccessToken');
        app()->instance(GoogleBusinessApiPort::class, $api);

        $response = $this->actingAs($user, 'stateful-api')
            ->getJson('/api/v2/ai/google/reviews')
            ->assertStatus(200);

        expect($response->json('data.data'))->toHaveCount(1);
    });

    it('refreshes the token when expired, persists new tokens, then fetches with the new token', function (): void {
        $user = User::factory()->create(['email' => 'refresh-reviews@test.com']);
        giveAiPermission($user);

        UserGoogleBusinessConnection::create([
            'user_id'              => $user->id,
            'google_account_id'    => 'acc-777',
            'business_location_id' => 'loc-123',
            'business_name'        => 'Biz',
            'access_token'         => 'tok-old',
            'refresh_token'        => 'ref-xyz',
            'token_expires_at'     => now()->subMinutes(10),
        ]);

        $api = Mockery::mock(GoogleBusinessApiPort::class);
        $api->shouldReceive('refreshAccessToken')
            ->once()
            ->with('ref-xyz')
            ->andReturn(['access_token' => 'tok-new', 'expires_in' => 3600]);
        $api->shouldReceive('fetchPendingReviews')
            ->once()
            ->with('tok-new', 'acc-777', 'loc-123')
            ->andReturn([]);
        app()->instance(GoogleBusinessApiPort::class, $api);

        $this->actingAs($user, 'stateful-api')
            ->getJson('/api/v2/ai/google/reviews')
            ->assertStatus(200);

        $fresh = UserGoogleBusinessConnection::where('user_id', $user->id)->firstOrFail();
        expect($fresh->access_token)->toBe('tok-new')
            ->and($fresh->token_expires_at->greaterThan(now()))->toBeTrue();
    });
});
