<?php

declare(strict_types=1);

namespace App\Http\Contracts;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

final class HttpJson
{
	public static function OK(mixed $data, int $status = 200): JsonResponse
	{
		return response()->json([
			'data' => $data,
		], $status);
	}

	public static function KO(string $message, int $status = 500, array $extra = []): JsonResponse
	{
		Log::error(json_encode(['message' => $message, 'data' => $extra]));
		return response()->json([
			'error' => [
				'message' => $message,
				...$extra,
			],
		], $status);
	}

	public static function response(): Response
	{
		return response();
	}
}
