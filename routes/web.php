<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\GoHighLevelOAuthController;
use App\Http\Controllers\Product\Web\RedirectionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
	return redirect()->away('https://messagenes.com');
});

Route::get('/nfc/{data}', [RedirectionController::class, 'legacyRedirect']);
Route::get('/product/{id}/redirect/{password}', [RedirectionController::class, 'v2Redirect']);

/**
 * Bracelet Test
 */
Route::get('bracelet/test/{id}', function ($id) {
	$product = App\Models\Product::findById((int) $id);
	$productDto = App\Infrastructure\DTO\ProductDto::fromModel($product);

	dump($productDto);
});

/**
 * B4A API
 */
Route::prefix('jobs')->group(function () {
	Route::get('/test', function () {
		echo 'dispatching...';

		App\Jobs\TestJob::dispatch();
	});
});

/**
 * GHL External API
 */
Route::prefix('crm')->group(function () {
	Route::get('oauth/connect', function (){
		$url = str_replace('<<CLIENT_ID>>', env('GHL_OAUTH_CLIENT_ID'), env('GHL_OAUTH_URL'));

		return redirect()->away($url);
	});
	Route::get('oauth/callback', [GoHighLevelOAuthController::class, 'authCode']);
});
