<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
*/

// Check for expiring subscriptions daily
Schedule::command('subscriptions:check-expiring')
    ->daily()
    ->description('Send reminders for expiring subscriptions');

// Process subscription renewals
Schedule::command('subscriptions:process-renewals')
    ->daily()
    ->description('Process automatic subscription renewals');

// Clean up expired carts
Schedule::command('carts:cleanup')
    ->daily()
    ->description('Remove expired shopping carts');

// Send low stock alerts
Schedule::command('inventory:low-stock-alerts')
    ->dailyAt('09:00')
    ->description('Send low stock alerts to merchants');

// Generate daily reports
Schedule::command('reports:generate-daily')
    ->dailyAt('01:00')
    ->description('Generate daily sales reports');

// Clean up old activity logs
Schedule::command('activitylog:clean --days=90')
    ->weekly()
    ->description('Clean up old activity logs');
