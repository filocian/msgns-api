<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Src\GoogleBusiness\Domain\Errors\GoogleBusinessUnavailable;
use Src\GoogleBusiness\Infrastructure\Adapters\GoogleBusinessApiAdapter;

beforeEach(function (): void {
    config()->set('services.google.client_id', 'test-client-id');
    config()->set('services.google.client_secret', 'test-client-secret');
    config()->set('services.google.business_redirect_uri', 'http://localhost/google-business/callback');
});

describe('GoogleBusinessApiAdapter', function (): void {

    it('fetches pending reviews with correct authorization header', function (): void {
        Http::fake([
            'https://mybusiness.googleapis.com/v4/locations/loc-123/reviews*' => Http::response([
                'reviews' => [
                    ['reviewId' => 'rev-1', 'comment' => 'Great place!'],
                ],
            ], 200),
        ]);

        $adapter = new GoogleBusinessApiAdapter();
        $reviews = $adapter->fetchPendingReviews('my-access-token', 'loc-123');

        expect($reviews)->toHaveCount(1)
            ->and($reviews[0]['reviewId'])->toBe('rev-1');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'loc-123/reviews')
            && $request->hasHeader('Authorization', 'Bearer my-access-token'));
    });

    it('posts review reply with correct payload', function (): void {
        Http::fake([
            'https://mybusiness.googleapis.com/v4/locations/loc-123/reviews/rev-456/reply' => Http::response([], 200),
        ]);

        $adapter = new GoogleBusinessApiAdapter();
        $adapter->postReviewReply('my-access-token', 'loc-123', 'rev-456', 'Thank you for your feedback!');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'rev-456/reply')
            && $request->data()['comment'] === 'Thank you for your feedback!');
    });

    it('refreshes access token', function (): void {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'new-access-token',
                'expires_in'   => 3600,
            ], 200),
        ]);

        $adapter = new GoogleBusinessApiAdapter();
        $result  = $adapter->refreshAccessToken('my-refresh-token');

        expect($result['access_token'])->toBe('new-access-token')
            ->and($result['expires_in'])->toBe(3600);
    });

    it('throws GoogleBusinessUnavailable on http failure', function (): void {
        Http::fake([
            'https://mybusiness.googleapis.com/*' => Http::response([], 500),
        ]);

        $adapter = new GoogleBusinessApiAdapter();

        expect(fn () => $adapter->fetchPendingReviews('token', 'loc-123'))
            ->toThrow(GoogleBusinessUnavailable::class);
    });

    it('throws GoogleBusinessUnavailable on connection timeout', function (): void {
        Http::fake([
            'https://mybusiness.googleapis.com/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
            },
        ]);

        $adapter = new GoogleBusinessApiAdapter();

        expect(fn () => $adapter->fetchPendingReviews('token', 'loc-123'))
            ->toThrow(GoogleBusinessUnavailable::class);
    });

    it('throws GoogleBusinessUnavailable on 429 rate limit', function (): void {
        Http::fake([
            'https://mybusiness.googleapis.com/*' => Http::response([], 429),
        ]);

        $adapter = new GoogleBusinessApiAdapter();

        expect(fn () => $adapter->fetchPendingReviews('token', 'loc-123'))
            ->toThrow(GoogleBusinessUnavailable::class);
    });
});
