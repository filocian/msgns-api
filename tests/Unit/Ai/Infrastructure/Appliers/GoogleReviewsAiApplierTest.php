<?php

declare(strict_types=1);

use Src\Ai\Domain\DataTransferObjects\AiResponseRecord;
use Src\Ai\Infrastructure\Appliers\GoogleReviewsAiApplier;
use Src\GoogleBusiness\Domain\Errors\GoogleBusinessConnectionNotFound;
use Src\GoogleBusiness\Domain\Models\UserGoogleBusinessConnection;
use Src\GoogleBusiness\Domain\Ports\GoogleBusinessApiPort;
use Src\GoogleBusiness\Domain\Ports\GoogleBusinessConnectionRepositoryPort;

afterEach(fn () => Mockery::close());

function makeReviewDto(
    string $productType = 'google_review',
    ?string $editedContent = null,
    string $aiContent = 'AI reply',
    string $reviewId = 'rev-abc',
    int $userId = 7,
): AiResponseRecord {
    return new AiResponseRecord(
        id: 'uuid-1',
        userId: $userId,
        productType: $productType,
        productId: 42,
        aiContent: $aiContent,
        editedContent: $editedContent,
        status: 'applied',
        metadata: ['review_id' => $reviewId],
        createdAt: new \DateTimeImmutable(),
    );
}

function makeConnection(bool $expired = false): UserGoogleBusinessConnection
{
    $conn                        = new UserGoogleBusinessConnection();
    $conn->user_id               = 7;
    $conn->google_account_id     = 'acc-777';
    $conn->business_location_id  = 'loc-123';
    $conn->business_name         = 'Test Biz';
    $conn->access_token          = 'token-current';
    $conn->refresh_token         = 'refresh-xyz';
    $conn->token_expires_at      = $expired ? now()->subMinutes(10) : now()->addHour();

    return $conn;
}

describe('GoogleReviewsAiApplier', function (): void {

    it('supports("google_review") returns true', function (): void {
        $applier = new GoogleReviewsAiApplier(
            Mockery::mock(GoogleBusinessConnectionRepositoryPort::class),
            Mockery::mock(GoogleBusinessApiPort::class),
        );

        expect($applier->supports('google_review'))->toBeTrue();
    });

    it('supports other product types returns false', function (): void {
        $applier = new GoogleReviewsAiApplier(
            Mockery::mock(GoogleBusinessConnectionRepositoryPort::class),
            Mockery::mock(GoogleBusinessApiPort::class),
        );

        expect($applier->supports('instagram_content'))->toBeFalse();
    });

    it('apply() with non-expired token uses current access_token and calls postReviewReply', function (): void {
        $dto  = makeReviewDto(aiContent: 'Hello, thank you!');
        $conn = makeConnection(expired: false);

        $repo = Mockery::mock(GoogleBusinessConnectionRepositoryPort::class);
        $repo->shouldReceive('findByUserId')->with(7)->andReturn($conn);
        $repo->shouldNotReceive('updateTokens');

        $api = Mockery::mock(GoogleBusinessApiPort::class);
        $api->shouldNotReceive('refreshAccessToken');
        $api->shouldReceive('postReviewReply')
            ->once()
            ->with('token-current', 'acc-777', 'loc-123', 'rev-abc', 'Hello, thank you!');

        (new GoogleReviewsAiApplier($repo, $api))->apply($dto);
    });

    it('apply() with expired token refreshes, persists, and uses the new access_token', function (): void {
        $dto  = makeReviewDto(aiContent: 'Thanks!');
        $conn = makeConnection(expired: true);

        $repo = Mockery::mock(GoogleBusinessConnectionRepositoryPort::class);
        $repo->shouldReceive('findByUserId')->with(7)->andReturn($conn);
        $repo->shouldReceive('updateTokens')->once()->with(7, 'fresh-token', 3600);

        $api = Mockery::mock(GoogleBusinessApiPort::class);
        $api->shouldReceive('refreshAccessToken')
            ->once()
            ->with('refresh-xyz')
            ->andReturn(['access_token' => 'fresh-token', 'expires_in' => 3600]);
        $api->shouldReceive('postReviewReply')
            ->once()
            ->with('fresh-token', 'acc-777', 'loc-123', 'rev-abc', 'Thanks!');

        (new GoogleReviewsAiApplier($repo, $api))->apply($dto);
    });

    it('apply() prefers editedContent over aiContent when present', function (): void {
        $dto  = makeReviewDto(editedContent: 'Edited reply', aiContent: 'AI original');
        $conn = makeConnection(expired: false);

        $repo = Mockery::mock(GoogleBusinessConnectionRepositoryPort::class);
        $repo->shouldReceive('findByUserId')->andReturn($conn);

        $api = Mockery::mock(GoogleBusinessApiPort::class);
        $api->shouldReceive('postReviewReply')
            ->once()
            ->with('token-current', 'acc-777', 'loc-123', 'rev-abc', 'Edited reply');

        (new GoogleReviewsAiApplier($repo, $api))->apply($dto);
    });

    it('apply() throws InvalidArgumentException when productType is not google_review', function (): void {
        $dto = makeReviewDto(productType: 'instagram_content');

        $applier = new GoogleReviewsAiApplier(
            Mockery::mock(GoogleBusinessConnectionRepositoryPort::class),
            Mockery::mock(GoogleBusinessApiPort::class),
        );

        expect(fn () => $applier->apply($dto))->toThrow(\InvalidArgumentException::class);
    });

    it('apply() throws GoogleBusinessConnectionNotFound when no connection exists', function (): void {
        $dto = makeReviewDto();

        $repo = Mockery::mock(GoogleBusinessConnectionRepositoryPort::class);
        $repo->shouldReceive('findByUserId')->with(7)->andReturn(null);

        $api = Mockery::mock(GoogleBusinessApiPort::class);
        $api->shouldNotReceive('postReviewReply');

        $applier = new GoogleReviewsAiApplier($repo, $api);

        expect(fn () => $applier->apply($dto))->toThrow(GoogleBusinessConnectionNotFound::class);
    });
});
