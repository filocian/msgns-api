<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
	$this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('app:reset-stuck-on-assigned-products-command')->daily()->at('08:00');
Schedule::command('app:reset-non-existent-owner-products')->sundays()->at('07:50');
Schedule::command('ai:reset-free-usage')->monthlyOn(1, '00:00');
