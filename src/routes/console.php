<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

// Pre-warm the dashboard's tgju rate cache. Inert unless a host cron runs
// `schedule:run` every minute; the dashboard works without it regardless,
// because getRates() self-refreshes via Cache::remember on page load.
Schedule::command('app:refresh-rates')
    ->everyThirtyMinutes()
    ->withoutOverlapping();
