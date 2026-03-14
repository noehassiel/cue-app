<?php

use App\Console\Commands\GenerateProjectedTransactions;
use App\Jobs\SendDeficitAlerts;
use App\Jobs\SendPaymentReminders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(GenerateProjectedTransactions::class)->daily();
Schedule::job(SendPaymentReminders::class)->dailyAt('08:00');
Schedule::job(SendDeficitAlerts::class)->dailyAt('08:30');
