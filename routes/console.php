<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Scheduled jobs ───────────────────────────────────────────────
Schedule::command('queue:work --stop-when-empty --max-jobs=50')->everyMinute()->withoutOverlapping();
Schedule::command('operalyn:expire-reviews')->dailyAt('02:00');
Schedule::command('queue:prune-failed --hours=168')->dailyAt('03:00');
Schedule::command('sanctum:prune-expired --hours=720')->weekly();
