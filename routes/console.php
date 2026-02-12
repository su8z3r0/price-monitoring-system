<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule automatic proxy updates every hour
// Free proxies die quickly, so we need frequent refreshes
Schedule::command('cyper:proxies:update')->hourly();
