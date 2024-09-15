<?php

declare(strict_types=1);

use App\Http\Controllers\Product\Web\RedirectionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
	return view('welcome');
});

Route::get('/nfc/{data}', [RedirectionController::class, 'legacyRedirect']);
Route::get('/product/{id}/redirect/{password}', [RedirectionController::class, 'v2Redirect']);

/**
 * B4A API
 */
Route::prefix('jobs')->group(function () {
	Route::get('/test', function () {
		echo 'dispatching...';

		App\Jobs\TestJob::dispatch();
	});
});
