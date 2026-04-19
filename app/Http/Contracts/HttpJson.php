<?php

declare(strict_types=1);

namespace App\Http\Contracts;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Src\Shared\Infrastructure\Http\ErrorResponseFactory;

final class HttpJson
{
	public static function OK(mixed $data, int $status = 200): JsonResponse
	{
		return response()->json([
			'data' => $data,
		], $status);
	}

	/**
	 * @param array<string, mixed> $context
	 */
	public static function error(string $code, int $status = 500, array $context = []): JsonResponse
	{
		return ErrorResponseFactory::error($code, $status, $context);
	}

	/**
	 * @param array<string, mixed> $extra
	 */
	public static function KO(string $message, int $status = 500, array $extra = []): JsonResponse
	{
		return self::error($message, $status, $extra);
	}

	/**
	 * @param array<string, mixed> $errors
	 */
	public static function validationFailed(array $errors, int $status = 422): JsonResponse
	{
		return ErrorResponseFactory::validationFailed($errors, $status);
	}

	public static function response(): Response
	{
		return response();
	}
}
