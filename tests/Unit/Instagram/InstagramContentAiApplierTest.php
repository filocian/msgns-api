<?php

declare(strict_types=1);

use Src\Ai\Domain\DataTransferObjects\AiResponseRecord;
use Src\Ai\Domain\ValueObjects\AiProductType;
use Src\Instagram\Domain\DataTransferObjects\InstagramConnection;
use Src\Instagram\Domain\Errors\InstagramApiUnavailable;
use Src\Instagram\Domain\Errors\InstagramConfigurationMissing;
use Src\Instagram\Domain\Errors\InstagramPublishingFailed;
use Src\Instagram\Domain\Ports\InstagramConnectionRepositoryPort;
use Src\Instagram\Domain\Ports\InstagramGraphApiPort;
use Src\Instagram\Domain\Ports\InstagramProductConfigurationPort;
use Src\Instagram\Infrastructure\Appliers\InstagramContentAiApplier;
use Src\Shared\Core\Ports\LogPort;

afterEach(fn () => Mockery::close());

/**
 * @param array<string, mixed> $overrides
 */
function makeInstagramRecord(array $overrides = []): AiResponseRecord
{
    $defaults = [
        'id'            => 'rec-1',
        'userId'        => 7,
        'productType'   => AiProductType::INSTAGRAM_CONTENT->value,
        'productId'     => 42,
        'aiContent'     => 'Draft caption',
        'editedContent' => null,
        'status'        => 'approved',
        'metadata'      => ['s3_image_url' => 'https://s3.example.com/ai-media/7/2026/04/18/uuid.jpg'],
        'createdAt'     => new \DateTimeImmutable(),
    ];

    $data = array_merge($defaults, $overrides);

    return new AiResponseRecord(
        id: $data['id'],
        userId: $data['userId'],
        productType: $data['productType'],
        productId: $data['productId'],
        aiContent: $data['aiContent'],
        editedContent: $data['editedContent'],
        status: $data['status'],
        metadata: $data['metadata'],
        createdAt: $data['createdAt'],
    );
}

function makeValidInstagramConnection(int $userId = 7): InstagramConnection
{
    return new InstagramConnection(
        userId: $userId,
        accessToken: 'EAAG_TOKEN',
        instagramUserId: '17841400000000001',
        instagramUsername: 'filocian_test',
        pageId: '104000000000001',
        expiresAt: (new \DateTimeImmutable())->modify('+30 days'),
    );
}

describe('InstagramContentAiApplier::supports', function (): void {

    it('returns true for instagram_content', function (): void {
        $applier = new InstagramContentAiApplier(
            Mockery::mock(InstagramConnectionRepositoryPort::class),
            Mockery::mock(InstagramProductConfigurationPort::class),
            Mockery::mock(InstagramGraphApiPort::class),
            Mockery::mock(LogPort::class),
        );

        expect($applier->supports(AiProductType::INSTAGRAM_CONTENT->value))->toBeTrue();
    });

    it('returns false for google_review', function (): void {
        $applier = new InstagramContentAiApplier(
            Mockery::mock(InstagramConnectionRepositoryPort::class),
            Mockery::mock(InstagramProductConfigurationPort::class),
            Mockery::mock(InstagramGraphApiPort::class),
            Mockery::mock(LogPort::class),
        );

        expect($applier->supports(AiProductType::GOOGLE_REVIEW->value))->toBeFalse();
    });

    it('returns false for an unknown product type', function (): void {
        $applier = new InstagramContentAiApplier(
            Mockery::mock(InstagramConnectionRepositoryPort::class),
            Mockery::mock(InstagramProductConfigurationPort::class),
            Mockery::mock(InstagramGraphApiPort::class),
            Mockery::mock(LogPort::class),
        );

        expect($applier->supports('anything-else'))->toBeFalse();
    });
});

describe('InstagramContentAiApplier::apply — happy path', function (): void {

    it('calls createMediaContainer → waitForContainerReady → publishMediaContainer in that order', function (): void {
        $record     = makeInstagramRecord();
        $connection = makeValidInstagramConnection();
        $accountId  = '17841400000000010';

        $connections = Mockery::mock(InstagramConnectionRepositoryPort::class);
        $connections->shouldReceive('findByUserId')->with($record->userId)->andReturn($connection);

        $productConfig = Mockery::mock(InstagramProductConfigurationPort::class);
        $productConfig->shouldReceive('getInstagramAccountIdForProduct')->with($record->productId)->andReturn($accountId);

        $graphApi = Mockery::mock(InstagramGraphApiPort::class);
        $graphApi->shouldReceive('createMediaContainer')
            ->once()
            ->ordered()
            ->with($accountId, $record->metadata['s3_image_url'], $record->aiContent, $connection->accessToken)
            ->andReturn(['id' => 'creation-xyz']);
        $graphApi->shouldReceive('waitForContainerReady')
            ->once()
            ->ordered()
            ->with($accountId, 'creation-xyz', $connection->accessToken);
        $graphApi->shouldReceive('publishMediaContainer')
            ->once()
            ->ordered()
            ->with($accountId, 'creation-xyz', $connection->accessToken)
            ->andReturn(['id' => 'media-xyz']);

        $log = Mockery::mock(LogPort::class);
        $log->shouldReceive('info')->zeroOrMoreTimes();

        $applier = new InstagramContentAiApplier($connections, $productConfig, $graphApi, $log);

        $applier->apply($record);
    });

    it('uses editedContent when present, otherwise aiContent', function (): void {
        $record = makeInstagramRecord([
            'aiContent'     => 'AI draft',
            'editedContent' => 'User-edited caption',
        ]);

        $connections = Mockery::mock(InstagramConnectionRepositoryPort::class);
        $connections->shouldReceive('findByUserId')->andReturn(makeValidInstagramConnection());

        $productConfig = Mockery::mock(InstagramProductConfigurationPort::class);
        $productConfig->shouldReceive('getInstagramAccountIdForProduct')->andReturn('acct');

        $graphApi = Mockery::mock(InstagramGraphApiPort::class);
        $graphApi->shouldReceive('createMediaContainer')
            ->once()
            ->withArgs(fn ($a, $i, $c, $t): bool => $c === 'User-edited caption')
            ->andReturn(['id' => 'cid']);
        $graphApi->shouldReceive('waitForContainerReady')->once();
        $graphApi->shouldReceive('publishMediaContainer')->once()->andReturn(['id' => 'mid']);

        $log = Mockery::mock(LogPort::class);
        $log->shouldReceive('info')->zeroOrMoreTimes();

        $applier = new InstagramContentAiApplier($connections, $productConfig, $graphApi, $log);

        $applier->apply($record);
    });
});

describe('InstagramContentAiApplier::apply — configuration errors', function (): void {

    it('throws InstagramConfigurationMissing with reason missing_image_url when metadata.s3_image_url is null', function (): void {
        $record = makeInstagramRecord(['metadata' => ['s3_image_url' => null]]);

        $connections   = Mockery::mock(InstagramConnectionRepositoryPort::class);
        $productConfig = Mockery::mock(InstagramProductConfigurationPort::class);
        $graphApi      = Mockery::mock(InstagramGraphApiPort::class);
        $log           = Mockery::mock(LogPort::class);

        $applier = new InstagramContentAiApplier($connections, $productConfig, $graphApi, $log);

        try {
            $applier->apply($record);
            expect(true)->toBeFalse('Expected InstagramConfigurationMissing');
        } catch (InstagramConfigurationMissing $e) {
            expect($e->context()['reason'])->toBe('missing_image_url');
        }
    });

    it('throws InstagramConfigurationMissing with reason missing_oauth_token when no connection exists', function (): void {
        $record = makeInstagramRecord();

        $connections = Mockery::mock(InstagramConnectionRepositoryPort::class);
        $connections->shouldReceive('findByUserId')->andReturn(null);

        $productConfig = Mockery::mock(InstagramProductConfigurationPort::class);
        $graphApi      = Mockery::mock(InstagramGraphApiPort::class);
        $log           = Mockery::mock(LogPort::class);

        $applier = new InstagramContentAiApplier($connections, $productConfig, $graphApi, $log);

        try {
            $applier->apply($record);
            expect(true)->toBeFalse('Expected InstagramConfigurationMissing');
        } catch (InstagramConfigurationMissing $e) {
            expect($e->context()['reason'])->toBe('missing_oauth_token');
        }
    });

    it('throws InstagramConfigurationMissing with reason missing_oauth_token when the connection is expired', function (): void {
        $record = makeInstagramRecord();

        $expiredConnection = new InstagramConnection(
            userId: 7,
            accessToken: 'EAAG_TOKEN',
            instagramUserId: '1',
            instagramUsername: 'u',
            pageId: 'p',
            expiresAt: (new \DateTimeImmutable())->modify('-1 day'),
        );

        $connections = Mockery::mock(InstagramConnectionRepositoryPort::class);
        $connections->shouldReceive('findByUserId')->andReturn($expiredConnection);

        $productConfig = Mockery::mock(InstagramProductConfigurationPort::class);
        $graphApi      = Mockery::mock(InstagramGraphApiPort::class);
        $log           = Mockery::mock(LogPort::class);

        $applier = new InstagramContentAiApplier($connections, $productConfig, $graphApi, $log);

        try {
            $applier->apply($record);
            expect(true)->toBeFalse('Expected InstagramConfigurationMissing');
        } catch (InstagramConfigurationMissing $e) {
            expect($e->context()['reason'])->toBe('missing_oauth_token');
        }
    });

    it('throws InstagramConfigurationMissing with reason missing_instagram_account_id when the product has no configured account id', function (): void {
        $record = makeInstagramRecord();

        $connections = Mockery::mock(InstagramConnectionRepositoryPort::class);
        $connections->shouldReceive('findByUserId')->andReturn(makeValidInstagramConnection());

        $productConfig = Mockery::mock(InstagramProductConfigurationPort::class);
        $productConfig->shouldReceive('getInstagramAccountIdForProduct')->andReturn(null);

        $graphApi = Mockery::mock(InstagramGraphApiPort::class);
        $log      = Mockery::mock(LogPort::class);

        $applier = new InstagramContentAiApplier($connections, $productConfig, $graphApi, $log);

        try {
            $applier->apply($record);
            expect(true)->toBeFalse('Expected InstagramConfigurationMissing');
        } catch (InstagramConfigurationMissing $e) {
            expect($e->context()['reason'])->toBe('missing_instagram_account_id');
        }
    });
});

describe('InstagramContentAiApplier::apply — publishing errors', function (): void {

    it('rethrows as InstagramPublishingFailed(container_creation_failed) when createMediaContainer throws', function (): void {
        $record = makeInstagramRecord();

        $connections = Mockery::mock(InstagramConnectionRepositoryPort::class);
        $connections->shouldReceive('findByUserId')->andReturn(makeValidInstagramConnection());

        $productConfig = Mockery::mock(InstagramProductConfigurationPort::class);
        $productConfig->shouldReceive('getInstagramAccountIdForProduct')->andReturn('acct');

        $graphApi = Mockery::mock(InstagramGraphApiPort::class);
        $graphApi->shouldReceive('createMediaContainer')
            ->andThrow(InstagramApiUnavailable::because('media_container_creation_failed'));
        $graphApi->shouldNotReceive('waitForContainerReady');
        $graphApi->shouldNotReceive('publishMediaContainer');

        $log = Mockery::mock(LogPort::class);

        $applier = new InstagramContentAiApplier($connections, $productConfig, $graphApi, $log);

        try {
            $applier->apply($record);
            expect(true)->toBeFalse('Expected InstagramPublishingFailed');
        } catch (InstagramPublishingFailed $e) {
            expect($e->context()['reason'])->toBe('container_creation_failed');
        }
    });

    it('maps waitForContainerReady container_timeout to InstagramPublishingFailed(container_timeout)', function (): void {
        $record = makeInstagramRecord();

        $connections = Mockery::mock(InstagramConnectionRepositoryPort::class);
        $connections->shouldReceive('findByUserId')->andReturn(makeValidInstagramConnection());

        $productConfig = Mockery::mock(InstagramProductConfigurationPort::class);
        $productConfig->shouldReceive('getInstagramAccountIdForProduct')->andReturn('acct');

        $graphApi = Mockery::mock(InstagramGraphApiPort::class);
        $graphApi->shouldReceive('createMediaContainer')->andReturn(['id' => 'cid']);
        $graphApi->shouldReceive('waitForContainerReady')
            ->andThrow(InstagramApiUnavailable::because('container_timeout'));
        $graphApi->shouldNotReceive('publishMediaContainer');

        $log = Mockery::mock(LogPort::class);

        $applier = new InstagramContentAiApplier($connections, $productConfig, $graphApi, $log);

        try {
            $applier->apply($record);
            expect(true)->toBeFalse('Expected InstagramPublishingFailed');
        } catch (InstagramPublishingFailed $e) {
            expect($e->context()['reason'])->toBe('container_timeout')
                ->and($e->context())->toHaveKey('creation_id');
        }
    });

    it('maps waitForContainerReady container_status_error to InstagramPublishingFailed(container_status_error)', function (): void {
        $record = makeInstagramRecord();

        $connections = Mockery::mock(InstagramConnectionRepositoryPort::class);
        $connections->shouldReceive('findByUserId')->andReturn(makeValidInstagramConnection());

        $productConfig = Mockery::mock(InstagramProductConfigurationPort::class);
        $productConfig->shouldReceive('getInstagramAccountIdForProduct')->andReturn('acct');

        $graphApi = Mockery::mock(InstagramGraphApiPort::class);
        $graphApi->shouldReceive('createMediaContainer')->andReturn(['id' => 'cid']);
        $graphApi->shouldReceive('waitForContainerReady')
            ->andThrow(InstagramApiUnavailable::because('container_status_error', ['status_code' => 'ERROR']));
        $graphApi->shouldNotReceive('publishMediaContainer');

        $log = Mockery::mock(LogPort::class);

        $applier = new InstagramContentAiApplier($connections, $productConfig, $graphApi, $log);

        try {
            $applier->apply($record);
            expect(true)->toBeFalse('Expected InstagramPublishingFailed');
        } catch (InstagramPublishingFailed $e) {
            expect($e->context()['reason'])->toBe('container_status_error');
        }
    });

    it('rethrows as InstagramPublishingFailed(publish_failed) and logs a warning with creation_id when publishMediaContainer throws', function (): void {
        $record = makeInstagramRecord();

        $connections = Mockery::mock(InstagramConnectionRepositoryPort::class);
        $connections->shouldReceive('findByUserId')->andReturn(makeValidInstagramConnection());

        $productConfig = Mockery::mock(InstagramProductConfigurationPort::class);
        $productConfig->shouldReceive('getInstagramAccountIdForProduct')->andReturn('acct');

        $graphApi = Mockery::mock(InstagramGraphApiPort::class);
        $graphApi->shouldReceive('createMediaContainer')->andReturn(['id' => 'cid-42']);
        $graphApi->shouldReceive('waitForContainerReady');
        $graphApi->shouldReceive('publishMediaContainer')
            ->andThrow(InstagramApiUnavailable::because('media_publish_failed'));

        $log = Mockery::mock(LogPort::class);
        $log->shouldReceive('warning')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return ($context['creation_id'] ?? null) === 'cid-42';
            });

        $applier = new InstagramContentAiApplier($connections, $productConfig, $graphApi, $log);

        try {
            $applier->apply($record);
            expect(true)->toBeFalse('Expected InstagramPublishingFailed');
        } catch (InstagramPublishingFailed $e) {
            expect($e->context()['reason'])->toBe('publish_failed')
                ->and($e->context()['creation_id'])->toBe('cid-42');
        }
    });
});

describe('InstagramContentAiApplier::apply — defensive check', function (): void {

    it('throws InvalidArgumentException when record.productType is not instagram_content', function (): void {
        $record = makeInstagramRecord(['productType' => AiProductType::GOOGLE_REVIEW->value]);

        $applier = new InstagramContentAiApplier(
            Mockery::mock(InstagramConnectionRepositoryPort::class),
            Mockery::mock(InstagramProductConfigurationPort::class),
            Mockery::mock(InstagramGraphApiPort::class),
            Mockery::mock(LogPort::class),
        );

        expect(fn () => $applier->apply($record))->toThrow(\InvalidArgumentException::class);
    });
});
