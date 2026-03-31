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

	it('includes overview key when PaginatedResult has a non-null overview', function () {
		$result = new PaginatedResult(
			items: [['id' => 1]],
			currentPage: 1,
			perPage: 15,
			total: 1,
			lastPage: 1,
			overview: ['total_products' => 10, 'pending_configuration' => 3, 'paused' => 2],
		);

		$response = ApiResponseFactory::paginated($result);
		$data = $response->getData(true);

		expect($data)->toHaveKey('overview');
		expect($data['overview'])->toBe([
			'total_products' => 10,
			'pending_configuration' => 3,
			'paused' => 2,
		]);
	});

	it('excludes overview key when PaginatedResult has null overview', function () {
		$result = new PaginatedResult(
			items: [],
			currentPage: 1,
			perPage: 15,
			total: 0,
			lastPage: 1,
		);

		$response = ApiResponseFactory::paginated($result);
		$data = $response->getData(true);

		expect($data)->not->toHaveKey('overview');
		expect($data)->toHaveKeys(['data', 'meta']);
	});

	it('serialises overview after meta', function () {
		$result = new PaginatedResult(
			items: [],
			currentPage: 1,
			perPage: 15,
			total: 0,
			lastPage: 1,
			overview: ['total_products' => 5, 'pending_configuration' => 1, 'paused' => 0],
		);

		$response = ApiResponseFactory::paginated($result);
		$keys = array_keys($response->getData(true));

		expect($keys)->toBe(['data', 'meta', 'overview']);
	});
});
