<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Src\Shared\Core\Bus\PaginatedResult;

final class ApiResponseFactory
{
	public static function ok(mixed $data = null): JsonResponse
	{
		return response()->json(['data' => $data]);
	}

	public static function created(mixed $data = null): JsonResponse
	{
		return response()->json(['data' => $data], Response::HTTP_CREATED);
	}

	public static function noContent(): Response
	{
		return response()->noContent();
	}

	public static function paginated(PaginatedResult $result): JsonResponse
	{
		return response()->json([
			'data' => $result->items,
			'meta' => [
				'current_page' => $result->currentPage,
				'per_page' => $result->perPage,
				'total' => $result->total,
				'last_page' => $result->lastPage,
			],
		]);
	}
}
