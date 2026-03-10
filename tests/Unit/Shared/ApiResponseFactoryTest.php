<?php

declare(strict_types=1);

use Src\Shared\Core\Bus\PaginatedResult;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

describe('ApiResponseFactory', function () {
	it('returns paginated response with correct meta', function () {
		$response = ApiResponseFactory::paginated(new PaginatedResult([
			['id' => 'one'],
			['id' => 'two'],
		], 2, 10, 25, 3));

		expect($response->getData(true))->toBe([
			'data' => [
				['id' => 'one'],
				['id' => 'two'],
			],
			'meta' => [
				'current_page' => 2,
				'per_page' => 10,
				'total' => 25,
				'last_page' => 3,
			],
		]);
	});
});
