<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Src\Instagram\Domain\Errors\InstagramApiUnavailable;
use Src\Instagram\Infrastructure\Adapters\InstagramGraphApiAdapter;

beforeEach(function (): void {
    Config::set('services.meta.app_id', 'test-app-id');
    Config::set('services.meta.app_secret', 'test-app-secret');
});

describe('InstagramGraphApiAdapter::exchangeForLongLivedToken', function (): void {

    it('exchanges short lived token for long lived token using GET verb', function (): void {
        Http::fake([
            'https://graph.facebook.com/oauth/access_token*' => Http::response([
                'access_token' => 'long-lived-token',
                'expires_in'   => 5184000,
            ], 200),
        ]);

        $adapter = new InstagramGraphApiAdapter();
        $result  = $adapter->exchangeForLongLivedToken('short-lived-token');

        expect($result['access_token'])->toBe('long-lived-token')
            ->and($result['expires_in'])->toBe(5184000);

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'GET'
                && str_contains($request->url(), 'oauth/access_token')
                && str_contains($request->url(), 'fb_exchange_token=short-lived-token');
        });
    });

    it('throws InstagramApiUnavailable on token exchange failure', function (): void {
        Http::fake([
            'https://graph.facebook.com/oauth/access_token*' => Http::response([], 500),
        ]);

        $adapter = new InstagramGraphApiAdapter();

        expect(fn () => $adapter->exchangeForLongLivedToken('short-token'))
            ->toThrow(InstagramApiUnavailable::class);
    });

    it('throws InstagramApiUnavailable on connection timeout', function (): void {
        Http::fake(function (): never {
            throw new ConnectionException('timeout');
        });

        $adapter = new InstagramGraphApiAdapter();

        expect(fn () => $adapter->exchangeForLongLivedToken('short-token'))
            ->toThrow(InstagramApiUnavailable::class);
    });
});

describe('InstagramGraphApiAdapter::getInstagramBusinessAccountId', function (): void {

    it('fetches instagram business account id', function (): void {
        Http::fake([
            'https://graph.facebook.com/*/page-123*' => Http::response([
                'instagram_business_account' => ['id' => 'ig-user-456'],
            ], 200),
        ]);

        $adapter = new InstagramGraphApiAdapter();
        $result  = $adapter->getInstagramBusinessAccountId('page-123', 'test-token');

        expect($result['id'])->toBe('ig-user-456');
    });

    it('throws InstagramApiUnavailable on graph api failure', function (): void {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([], 500),
        ]);

        $adapter = new InstagramGraphApiAdapter();

        expect(fn () => $adapter->getInstagramBusinessAccountId('page-123', 'test-token'))
            ->toThrow(InstagramApiUnavailable::class);
    });
});

describe('InstagramGraphApiAdapter::createMediaContainer', function (): void {

    it('creates media container', function (): void {
        Http::fake([
            'https://graph.facebook.com/*/media' => Http::response([
                'id' => 'container-789',
            ], 200),
        ]);

        $adapter = new InstagramGraphApiAdapter();
        $result  = $adapter->createMediaContainer('ig-user-456', 'https://s3.example.com/image.jpg', 'Test caption', 'test-token');

        expect($result['id'])->toBe('container-789');
    });

    it('throws InstagramApiUnavailable on media container creation failure', function (): void {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([], 400),
        ]);

        $adapter = new InstagramGraphApiAdapter();

        expect(fn () => $adapter->createMediaContainer('ig-user-456', 'https://example.com/img.jpg', 'caption', 'token'))
            ->toThrow(InstagramApiUnavailable::class);
    });
});

describe('InstagramGraphApiAdapter::publishMediaContainer', function (): void {

    it('publishes media container', function (): void {
        Http::fake([
            'https://graph.facebook.com/*/media_publish' => Http::response([
                'id' => 'published-media-101',
            ], 200),
        ]);

        $adapter = new InstagramGraphApiAdapter();
        $result  = $adapter->publishMediaContainer('ig-user-456', 'container-789', 'test-token');

        expect($result['id'])->toBe('published-media-101');
    });

    it('throws InstagramApiUnavailable on publish failure', function (): void {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([], 500),
        ]);

        $adapter = new InstagramGraphApiAdapter();

        expect(fn () => $adapter->publishMediaContainer('ig-user-456', 'container-789', 'token'))
            ->toThrow(InstagramApiUnavailable::class);
    });

    it('throws InstagramApiUnavailable on connection exception', function (): void {
        Http::fake(function (): never {
            throw new ConnectionException('timeout');
        });

        $adapter = new InstagramGraphApiAdapter();

        expect(fn () => $adapter->publishMediaContainer('ig-user-456', 'container-789', 'token'))
            ->toThrow(InstagramApiUnavailable::class);
    });
});

describe('InstagramGraphApiAdapter::waitForContainerReady', function (): void {

    it('returns immediately when status_code is FINISHED on first poll', function (): void {
        Http::fake([
            'https://graph.facebook.com/*/creation-123*' => Http::response([
                'status_code' => 'FINISHED',
            ], 200),
        ]);

        $adapter = new TestableInstagramGraphApiAdapter();

        $adapter->waitForContainerReady('ig-user-456', 'creation-123', 'test-token');

        Http::assertSentCount(1);
        expect($adapter->sleepCount)->toBe(0);
    });

    it('returns after intermediate IN_PROGRESS polls then FINISHED', function (): void {
        Http::fake([
            'https://graph.facebook.com/*/creation-123*' => Http::sequence()
                ->push(['status_code' => 'IN_PROGRESS'], 200)
                ->push(['status_code' => 'IN_PROGRESS'], 200)
                ->push(['status_code' => 'FINISHED'], 200),
        ]);

        $adapter = new TestableInstagramGraphApiAdapter();

        $adapter->waitForContainerReady('ig-user-456', 'creation-123', 'test-token');

        Http::assertSentCount(3);
        // Sleep is called between polls, NOT after the final one → 2 sleeps for 3 polls.
        expect($adapter->sleepCount)->toBe(2);
    });

    it('throws InstagramApiUnavailable with reason container_status_error when status_code is ERROR', function (): void {
        Http::fake([
            'https://graph.facebook.com/*/creation-123*' => Http::response([
                'status_code' => 'ERROR',
            ], 200),
        ]);

        $adapter = new TestableInstagramGraphApiAdapter();

        try {
            $adapter->waitForContainerReady('ig-user-456', 'creation-123', 'test-token');
            expect(true)->toBeFalse('Expected InstagramApiUnavailable to be thrown');
        } catch (InstagramApiUnavailable $e) {
            expect($e->context()['reason'])->toBe('container_status_error')
                ->and($e->context()['status_code'] ?? null)->toBe('ERROR');
        }
    });

    it('throws InstagramApiUnavailable with reason container_status_error on non-2xx HTTP', function (): void {
        Http::fake([
            'https://graph.facebook.com/*/creation-123*' => Http::response([], 500),
        ]);

        $adapter = new TestableInstagramGraphApiAdapter();

        try {
            $adapter->waitForContainerReady('ig-user-456', 'creation-123', 'test-token');
            expect(true)->toBeFalse('Expected InstagramApiUnavailable to be thrown');
        } catch (InstagramApiUnavailable $e) {
            expect($e->context()['reason'])->toBe('container_status_error')
                ->and($e->context()['status_code'] ?? null)->toBe('http_500');
        }
    });

    it('throws InstagramApiUnavailable with reason container_timeout after 15 IN_PROGRESS polls', function (): void {
        Http::fake([
            'https://graph.facebook.com/*/creation-123*' => Http::response([
                'status_code' => 'IN_PROGRESS',
            ], 200),
        ]);

        $adapter = new TestableInstagramGraphApiAdapter();

        try {
            $adapter->waitForContainerReady('ig-user-456', 'creation-123', 'test-token');
            expect(true)->toBeFalse('Expected InstagramApiUnavailable to be thrown');
        } catch (InstagramApiUnavailable $e) {
            expect($e->context()['reason'])->toBe('container_timeout');
        }

        Http::assertSentCount(15);
        // Sleep called between polls, not after the final attempt → 14 sleeps.
        expect($adapter->sleepCount)->toBe(14);
    });

    it('throws InstagramApiUnavailable with reason connection_failed on ConnectionException', function (): void {
        Http::fake(function (): never {
            throw new ConnectionException('timeout');
        });

        $adapter = new TestableInstagramGraphApiAdapter();

        try {
            $adapter->waitForContainerReady('ig-user-456', 'creation-123', 'test-token');
            expect(true)->toBeFalse('Expected InstagramApiUnavailable to be thrown');
        } catch (InstagramApiUnavailable $e) {
            expect($e->context()['reason'])->toBe('connection_failed');
        }
    });
});

describe('InstagramGraphApiAdapter — config-driven graph API version', function (): void {

    it('uses services.meta.graph_api_version in outgoing URLs', function (): void {
        Config::set('services.meta.graph_api_version', 'v25.0');

        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'instagram_business_account' => ['id' => 'ig-user-999'],
            ], 200),
        ]);

        $adapter = new InstagramGraphApiAdapter();
        $adapter->getInstagramBusinessAccountId('page-123', 'test-token');

        Http::assertSent(function (Request $request): bool {
            return str_contains($request->url(), '/v25.0/');
        });
    });
});

/**
 * Test double that overrides sleep() to avoid real delays and counts calls.
 */
final class TestableInstagramGraphApiAdapter extends InstagramGraphApiAdapter
{
    public int $sleepCount = 0;

    protected function sleep(int $seconds): void
    {
        $this->sleepCount++;
    }
}
