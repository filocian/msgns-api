<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Billing\Infrastructure\Http\Controllers\StripeWebhookController;

Route::post('/stripe', StripeWebhookController::class);
