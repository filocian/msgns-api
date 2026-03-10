<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Shared\Core\Bus\PaginatedResult;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

describe('Shared API response factory feature integration', function () {
	it('returns ok payloads through the HTTP pipeline', function () {
		Route::get('/_test/feature/shared/responses/ok', fn () => ApiResponseFactory::ok([
			'id' => 'widget-1',
			'status' => 'ready',
		]));

		$this->getJson('/_test/feature/shared/responses/ok')
			->assertOk()
			->assertExactJson([
				'data' => [
					'id' => 'widget-1',
					'status' => 'ready',
				],
			]);
	});

	it('returns created payloads with a 201 status', function () {
		Route::post('/_test/feature/shared/responses/created', fn () => ApiResponseFactory::created([
			'id' => 'widget-2',
		]));

		$this->postJson('/_test/feature/shared/responses/created')
			->assertCreated()
			->assertExactJson([
				'data' => [
					'id' => 'widget-2',
				],
			]);
	});

	it('returns empty responses for no content', function () {
		Route::delete('/_test/feature/shared/responses/no-content', fn () => ApiResponseFactory::noContent());

		$response = $this->deleteJson('/_test/feature/shared/responses/no-content');

		$response->assertNoContent();
		expect($response->getContent())->toBe('');
	});

	it('returns paginated payloads with the expected metadata envelope', function () {
		Route::get('/_test/feature/shared/responses/paginated', fn () => ApiResponseFactory::paginated(new PaginatedResult(
			items: [
				['id' => 'widget-1'],
				['id' => 'widget-2'],
			],
			currentPage: 3,
			perPage: 2,
			total: 8,
			lastPage: 4,
		)));

		$this->getJson('/_test/feature/shared/responses/paginated')
			->assertOk()
			->assertExactJson([
				'data' => [
					['id' => 'widget-1'],
					['id' => 'widget-2'],
				],
				'meta' => [
					'current_page' => 3,
					'per_page' => 2,
					'total' => 8,
					'last_page' => 4,
				],
			]);
	});
});
