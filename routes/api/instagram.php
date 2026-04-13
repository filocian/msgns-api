<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Instagram\Infrastructure\Http\Controllers\InstagramConnectionController;

Route::get('/connection', [InstagramConnectionController::class, 'show']);
Route::delete('/connection', [InstagramConnectionController::class, 'destroy']);
