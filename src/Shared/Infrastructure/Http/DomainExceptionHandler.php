<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Http;

use Illuminate\Http\JsonResponse;
use Src\Shared\Core\Errors\DomainException;

final class DomainExceptionHandler
{
	public function render(DomainException $exception): JsonResponse
	{
		return response()->json([
			'error' => [
				'code' => $exception->errorCode(),
				'context' => $exception->context(),
			],
		], $exception->httpStatus());
	}
}
