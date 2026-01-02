<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Demo Mode Scheduler
|--------------------------------------------------------------------------
|
| When demo mode is enabled, reset the database every 6 hours to ensure
| a clean state for demo users.
|
*/
if (config('app.demo_mode')) {
    Schedule::command('demo:reset --force')
        ->everySixHours()
        ->runInBackground()
        ->withoutOverlapping();
}
