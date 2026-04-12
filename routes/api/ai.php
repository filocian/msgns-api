<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['auth:stateful-api', 'ai.rate-limit'])->group(function (): void {
    // AI routes — extended by BE-3, BE-8, BE-10, BE-12, BE-13
});
