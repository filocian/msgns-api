<?php

declare(strict_types=1);

namespace Src\Ai\Infrastructure\Adapters;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Src\Ai\Domain\DataTransferObjects\AiRequest;
use Src\Ai\Domain\DataTransferObjects\AiResponse;
use Src\Ai\Domain\Errors\GeminiUnavailable;
use Src\Ai\Domain\Ports\GeminiPort;

final class GeminiApiAdapter implements GeminiPort
{
    private const BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function generate(AiRequest $request): AiResponse
    {
        $apiKey = (string) config('services.gemini.api_key', '');
        $model = (string) config('services.gemini.model', 'gemini-2.0-flash');
        $timeout = (int) config('services.gemini.timeout_seconds', 30);

        if ($apiKey === '') {
            throw GeminiUnavailable::because('gemini_api_key_missing');
        }

        $url = self::BASE_URL."/{$model}:generateContent";

        $parts = [['text' => $request->prompt]];

        if ($request->imageBase64 !== null) {
            $parts[] = [
                'inline_data' => [
                    'mime_type' => $request->imageMimeType ?? 'image/jpeg',
                    'data' => $request->imageBase64,
                ],
            ];
        }

        $body = [
            'system_instruction' => ['parts' => [['text' => $request->systemInstruction]]],
            'contents' => [['role' => 'user', 'parts' => $parts]],
        ];

        try {
            $response = Http::timeout($timeout)->post($url.'?key='.$apiKey, $body);
        } catch (ConnectionException) {
            throw GeminiUnavailable::because('gemini_connection_failed');
        }

        if ($response->failed()) {
            throw GeminiUnavailable::because('gemini_http_error');
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw GeminiUnavailable::because('gemini_invalid_payload');
        }

        /** @var array<string, mixed> $payload */
        $content = (string) ($payload['candidates'][0]['content']['parts'][0]['text'] ?? '');
        $usage = $payload['usageMetadata'] ?? [];

        return new AiResponse(
            content: $content,
            promptTokens: (int) ($usage['promptTokenCount'] ?? 0),
            completionTokens: (int) ($usage['candidatesTokenCount'] ?? 0),
            totalTokens: (int) ($usage['totalTokenCount'] ?? 0),
        );
    }
}
