<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Errors\Unauthorized;
use Src\Shared\Core\Errors\ValidationFailed;

describe('Shared domain exception feature integration', function () {
	it('renders shared domain exceptions through the feature HTTP pipeline', function () {
		Route::get('/_test/feature/shared/not-found', fn () => throw NotFound::entity('widget', 'feature-123'));

		$this->getJson('/_test/feature/shared/not-found')
			->assertNotFound()
			->assertExactJson([
				'error' => [
					'code' => 'widget_not_found',
					'context' => ['id' => 'feature-123'],
				],
			]);
	});

	it('renders validation failures through the feature HTTP pipeline', function () {
		Route::get('/_test/feature/shared/validation', fn () => throw ValidationFailed::because('invalid_widget', [
			'field' => 'name',
			'reason' => 'required',
		]));

		$this->getJson('/_test/feature/shared/validation')
			->assertUnprocessable()
			->assertExactJson([
				'error' => [
					'code' => 'invalid_widget',
					'context' => [
						'field' => 'name',
						'reason' => 'required',
					],
				],
			]);
	});

	it('renders unauthorized domain exceptions with forbidden status', function () {
		Route::post('/_test/feature/shared/unauthorized', fn () => throw Unauthorized::because('not_allowed', [
			'action' => 'publish',
		]));

		$this->postJson('/_test/feature/shared/unauthorized')
			->assertForbidden()
			->assertExactJson([
				'error' => [
					'code' => 'not_allowed',
					'context' => [
						'action' => 'publish',
					],
				],
			]);
	});
});
