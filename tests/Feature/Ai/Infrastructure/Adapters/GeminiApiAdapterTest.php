<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Src\Ai\Domain\DataTransferObjects\AiRequest;
use Src\Ai\Domain\Errors\GeminiUnavailable;
use Src\Ai\Infrastructure\Adapters\GeminiApiAdapter;

beforeEach(function () {
    config()->set('services.gemini.api_key', 'test-gemini-key');
    config()->set('services.gemini.model', 'gemini-2.0-flash');
    config()->set('services.gemini.timeout_seconds', 30);
});

describe('GeminiApiAdapter', function () {
    it('parses a successful response with content and token counts', function () {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [['text' => 'Hello, world!']],
                    ],
                ]],
                'usageMetadata' => [
                    'promptTokenCount'     => 10,
                    'candidatesTokenCount' => 5,
                    'totalTokenCount'      => 15,
                ],
            ], 200),
        ]);

        $adapter  = new GeminiApiAdapter();
        $response = $adapter->generate(new AiRequest(
            prompt:            'Say hello',
            systemInstruction: 'You are a helpful assistant.',
        ));

        expect($response->content)->toBe('Hello, world!')
            ->and($response->promptTokens)->toBe(10)
            ->and($response->completionTokens)->toBe(5)
            ->and($response->totalTokens)->toBe(15);
    });

    it('throws GeminiUnavailable with gemini_api_key_missing when api key is empty', function () {
        config()->set('services.gemini.api_key', '');

        Http::fake();

        $adapter = new GeminiApiAdapter();

        expect(fn () => $adapter->generate(new AiRequest(
            prompt:            'Hello',
            systemInstruction: 'You are helpful.',
        )))->toThrow(GeminiUnavailable::class, 'gemini_api_key_missing');

        Http::assertNothingSent();
    });

    it('throws GeminiUnavailable with gemini_api_key_missing when api key is null', function () {
        config()->set('services.gemini.api_key', null);

        Http::fake();

        $adapter = new GeminiApiAdapter();

        expect(fn () => $adapter->generate(new AiRequest(
            prompt:            'Hello',
            systemInstruction: 'You are helpful.',
        )))->toThrow(GeminiUnavailable::class, 'gemini_api_key_missing');

        Http::assertNothingSent();
    });

    it('throws GeminiUnavailable with gemini_connection_failed on connection exception', function () {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection refused');
            },
        ]);

        $adapter = new GeminiApiAdapter();

        expect(fn () => $adapter->generate(new AiRequest(
            prompt:            'Hello',
            systemInstruction: 'You are helpful.',
        )))->toThrow(GeminiUnavailable::class, 'gemini_connection_failed');
    });

    it('throws GeminiUnavailable with gemini_http_error on non-2xx response', function () {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response(['error' => 'bad request'], 400),
        ]);

        $adapter = new GeminiApiAdapter();

        expect(fn () => $adapter->generate(new AiRequest(
            prompt:            'Hello',
            systemInstruction: 'You are helpful.',
        )))->toThrow(GeminiUnavailable::class, 'gemini_http_error');
    });

    it('throws GeminiUnavailable with gemini_http_error on 500 response', function () {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response(null, 500),
        ]);

        $adapter = new GeminiApiAdapter();

        expect(fn () => $adapter->generate(new AiRequest(
            prompt:            'Hello',
            systemInstruction: 'You are helpful.',
        )))->toThrow(GeminiUnavailable::class, 'gemini_http_error');
    });

    it('throws GeminiUnavailable with gemini_invalid_payload when response body is not an array', function () {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response('not json at all', 200),
        ]);

        $adapter = new GeminiApiAdapter();

        expect(fn () => $adapter->generate(new AiRequest(
            prompt:            'Hello',
            systemInstruction: 'You are helpful.',
        )))->toThrow(GeminiUnavailable::class, 'gemini_invalid_payload');
    });

    it('sends the api key as a query parameter and not as a bearer token', function () {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'ok']]],
                ]],
                'usageMetadata' => [
                    'promptTokenCount'     => 1,
                    'candidatesTokenCount' => 1,
                    'totalTokenCount'      => 2,
                ],
            ], 200),
        ]);

        $adapter = new GeminiApiAdapter();
        $adapter->generate(new AiRequest(
            prompt:            'Hello',
            systemInstruction: 'You are helpful.',
        ));

        Http::assertSent(function (Request $request): bool {
            return str_contains($request->url(), '?key=test-gemini-key')
                && !$request->hasHeader('Authorization');
        });
    });

    it('sends correct request body with system_instruction and contents', function () {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'response']]],
                ]],
                'usageMetadata' => [
                    'promptTokenCount'     => 2,
                    'candidatesTokenCount' => 1,
                    'totalTokenCount'      => 3,
                ],
            ], 200),
        ]);

        $adapter = new GeminiApiAdapter();
        $adapter->generate(new AiRequest(
            prompt:            'My prompt',
            systemInstruction: 'My system instruction',
        ));

        Http::assertSent(function (Request $request): bool {
            $body = $request->data();

            return isset($body['system_instruction']['parts'][0]['text'])
                && $body['system_instruction']['parts'][0]['text'] === 'My system instruction'
                && isset($body['contents'][0]['role'])
                && $body['contents'][0]['role'] === 'user'
                && isset($body['contents'][0]['parts'][0]['text'])
                && $body['contents'][0]['parts'][0]['text'] === 'My prompt';
        });
    });

    it('includes inline_data part alongside text when an image is provided', function () {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'image response']]],
                ]],
                'usageMetadata' => [
                    'promptTokenCount'     => 20,
                    'candidatesTokenCount' => 10,
                    'totalTokenCount'      => 30,
                ],
            ], 200),
        ]);

        $adapter = new GeminiApiAdapter();
        $adapter->generate(new AiRequest(
            prompt:            'Describe this image',
            systemInstruction: 'You are a vision assistant.',
            imageBase64:       base64_encode('fake-image-bytes'),
            imageMimeType:     'image/png',
        ));

        Http::assertSent(function (Request $request): bool {
            $parts = $request->data()['contents'][0]['parts'] ?? [];

            $hasText      = isset($parts[0]['text']) && $parts[0]['text'] === 'Describe this image';
            $hasInlineData = isset($parts[1]['inline_data']['mime_type'])
                && $parts[1]['inline_data']['mime_type'] === 'image/png'
                && isset($parts[1]['inline_data']['data']);

            return $hasText && $hasInlineData;
        });
    });

    it('defaults to image/jpeg mime type when imageBase64 is set but imageMimeType is null', function () {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'ok']]],
                ]],
                'usageMetadata' => [
                    'promptTokenCount'     => 1,
                    'candidatesTokenCount' => 1,
                    'totalTokenCount'      => 2,
                ],
            ], 200),
        ]);

        $adapter = new GeminiApiAdapter();
        $adapter->generate(new AiRequest(
            prompt:            'Describe this image',
            systemInstruction: 'You are a vision assistant.',
            imageBase64:       base64_encode('fake-image-bytes'),
            imageMimeType:     null,
        ));

        Http::assertSent(function (Request $request): bool {
            $parts = $request->data()['contents'][0]['parts'] ?? [];

            return isset($parts[1]['inline_data']['mime_type'])
                && $parts[1]['inline_data']['mime_type'] === 'image/jpeg';
        });
    });

    it('uses the configured model in the request url', function () {
        config()->set('services.gemini.model', 'gemini-1.5-pro');

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'ok']]],
                ]],
                'usageMetadata' => [
                    'promptTokenCount'     => 1,
                    'candidatesTokenCount' => 1,
                    'totalTokenCount'      => 2,
                ],
            ], 200),
        ]);

        $adapter = new GeminiApiAdapter();
        $adapter->generate(new AiRequest(
            prompt:            'Hello',
            systemInstruction: 'You are helpful.',
        ));

        Http::assertSent(function (Request $request): bool {
            return str_contains($request->url(), 'gemini-1.5-pro:generateContent');
        });
    });

    it('returns empty string content when candidates text is missing', function () {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates'    => [],
                'usageMetadata' => [],
            ], 200),
        ]);

        $adapter  = new GeminiApiAdapter();
        $response = $adapter->generate(new AiRequest(
            prompt:            'Hello',
            systemInstruction: 'You are helpful.',
        ));

        expect($response->content)->toBe('')
            ->and($response->promptTokens)->toBe(0)
            ->and($response->completionTokens)->toBe(0)
            ->and($response->totalTokens)->toBe(0);
    });
});
