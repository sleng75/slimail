<?php

use App\Jobs\ProcessAbTestWinnersJob;
use App\Jobs\ProcessAutomationEnrollmentsJob;
use App\Jobs\ProcessScheduledCampaignsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Process scheduled campaigns every minute
Schedule::job(new ProcessScheduledCampaignsJob())->everyMinute()->withoutOverlapping();

// Process A/B test winner determination every 5 minutes
Schedule::job(new ProcessAbTestWinnersJob())->everyFiveMinutes()->withoutOverlapping();

// Process automation enrollments every minute
Schedule::job(new ProcessAutomationEnrollmentsJob())->everyMinute()->withoutOverlapping();

// Process subscriptions (expiry, reminders) daily at 8 AM
Schedule::command('subscriptions:process')->dailyAt('08:00')->withoutOverlapping();

// Reset monthly usage on the 1st of each month at midnight
Schedule::command('subscriptions:process --reset-usage')->monthlyOn(1, '00:00');

// Database backup every night at 2 AM
Schedule::command('backup:database --compress --upload')->dailyAt('02:00')->withoutOverlapping();

// Clean up old data weekly on Sundays at 3 AM
Schedule::command('cleanup:data --all --days=90')->weeklyOn(0, '03:00')->withoutOverlapping();

// Health check every 5 minutes
Schedule::command('health:check --notify')->everyFiveMinutes()->withoutOverlapping();

// Clear expired cache daily at 4 AM
Schedule::command('cache:prune-stale-tags')->dailyAt('04:00');

// Horizon snapshot every 5 minutes (for dashboard metrics)
Schedule::command('horizon:snapshot')->everyFiveMinutes();
