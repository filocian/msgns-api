<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Shared\Core\Bus\PaginatedResult;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

describe('ApiResponseFactory HTTP integration', function () {
	it('returns ok payloads through laravel responses', function () {
		Route::get('/_test/shared/responses/ok', fn () => ApiResponseFactory::ok(['id' => 'widget-1']));

		$this->getJson('/_test/shared/responses/ok')
			->assertOk()
			->assertExactJson([
				'data' => ['id' => 'widget-1'],
			]);
	});

	it('returns created payloads with 201 status', function () {
		Route::post('/_test/shared/responses/created', fn () => ApiResponseFactory::created(['id' => 'widget-2']));

		$this->postJson('/_test/shared/responses/created')
			->assertCreated()
			->assertExactJson([
				'data' => ['id' => 'widget-2'],
			]);
	});

	it('returns no content responses', function () {
		Route::delete('/_test/shared/responses/no-content', fn () => ApiResponseFactory::noContent());

		$this->deleteJson('/_test/shared/responses/no-content')
			->assertNoContent();
	});

	it('returns paginated payloads with metadata', function () {
		Route::get('/_test/shared/responses/paginated', fn () => ApiResponseFactory::paginated(new PaginatedResult([
			['id' => 'widget-1'],
			['id' => 'widget-2'],
		], 3, 2, 8, 4)));

		$this->getJson('/_test/shared/responses/paginated')
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
