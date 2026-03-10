<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Shared\Core\Errors\NotFound;
use Src\Shared\Core\Errors\Unauthorized;
use Src\Shared\Core\Errors\ValidationFailed;

describe('Shared domain exception HTTP integration', function () {
	it('renders not found exceptions through the laravel exception pipeline', function () {
		Route::get('/_test/shared/not-found', fn () => throw NotFound::entity('widget', 'abc-123'));

		$this->getJson('/_test/shared/not-found')
			->assertNotFound()
			->assertExactJson([
				'error' => [
					'code' => 'widget_not_found',
					'context' => ['id' => 'abc-123'],
				],
			]);
	});

	it('renders validation failures through the shared handler', function () {
		Route::get('/_test/shared/validation', fn () => throw ValidationFailed::because('invalid_widget', ['field' => 'name']));

		$this->getJson('/_test/shared/validation')
			->assertUnprocessable()
			->assertExactJson([
				'error' => [
					'code' => 'invalid_widget',
					'context' => ['field' => 'name'],
				],
			]);
	});

	it('renders unauthorized domain exceptions with forbidden status', function () {
		Route::get('/_test/shared/unauthorized', fn () => throw Unauthorized::because('not_allowed', ['action' => 'publish']));

		$this->getJson('/_test/shared/unauthorized')
			->assertForbidden()
			->assertExactJson([
				'error' => [
					'code' => 'not_allowed',
					'context' => ['action' => 'publish'],
				],
			]);
	});
});
