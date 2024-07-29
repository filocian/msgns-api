<?php

declare(strict_types=1);

use App\Http\Controllers\Product\Web\RedirectionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
	return view('welcome');
});

Route::get('/nfc/{id}', [RedirectionController::class, 'legacyRedirect']);
Route::get('/product/{id}/redirect/{password}', [RedirectionController::class, 'v2Redirect']);
