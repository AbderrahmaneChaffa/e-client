<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('notifications:sync-alerts')->hourly();

if (config('imports.cleanup.enabled', true)) {
    Schedule::command('imports:cleanup', [
        '--force' => true,
        '--days' => config('imports.cleanup.retention_days', 30),
    ])
        ->dailyAt(config('imports.cleanup.schedule_at', '02:00'))
        ->withoutOverlapping()
        ->onOneServer()
        ->runInBackground();
}
