<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Http;

use Illuminate\Http\JsonResponse;

final class ErrorResponseFactory
{
	/**
	 * @param array<string, mixed> $context
	 */
	public static function error(string $code, int $status = 500, array $context = []): JsonResponse
	{
		return response()->json([
			'error' => [
				'code' => $code,
				'context' => $context,
			],
		], $status);
	}

	/**
	 * @param array<string, mixed> $errors
	 */
	public static function validationFailed(array $errors, int $status = 422): JsonResponse
	{
		return self::error('validation_failed', $status, [
			'errors' => $errors,
		]);
	}
}
