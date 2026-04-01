<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Places\Infrastructure\Http\Controllers\PlaceSearchController;

Route::middleware(['auth:stateful-api', 'throttle:12,1'])->group(function (): void {
	Route::get('/search', PlaceSearchController::class);
});
