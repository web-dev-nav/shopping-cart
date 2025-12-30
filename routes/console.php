<?php

use App\Jobs\SendDailySalesReportJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    // Run inline so the report is sent even when no queue worker is running.
    SendDailySalesReportJob::dispatchSync();
})->dailyAt(env('DAILY_SALES_REPORT_TIME', '20:00'))
    ->timezone(config('app.timezone', 'UTC'))
    ->name('daily-sales-report');
