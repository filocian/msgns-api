<?php

declare(strict_types=1);

use Src\Shared\Core\Bus\PaginatedResult;

describe('PaginatedResult', function () {
	it('constructs with 5 args and overview defaults to null (backward compat)', function () {
		$result = new PaginatedResult(
			items: [],
			currentPage: 1,
			perPage: 15,
			total: 0,
			lastPage: 1,
		);

		expect($result->overview)->toBeNull();
	});

	it('constructs with overview array', function () {
		$overview = ['total_products' => 10, 'pending_configuration' => 3, 'paused' => 2];

		$result = new PaginatedResult(
			items: [],
			currentPage: 1,
			perPage: 15,
			total: 10,
			lastPage: 1,
			overview: $overview,
		);

		expect($result->overview)->toBe($overview);
	});

	it('accepts null overview explicitly', function () {
		$result = new PaginatedResult(
			items: [],
			currentPage: 1,
			perPage: 15,
			total: 0,
			lastPage: 1,
			overview: null,
		);

		expect($result->overview)->toBeNull();
	});
});
