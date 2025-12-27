<?php

use App\Jobs\SendDailySalesReportJob;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    // Run inline so the report is sent even when no queue worker is running.
    try {
        $tz = config('app.timezone', 'UTC');
        $targetTime = env('DAILY_SALES_REPORT_TIME', '20:00');

        $now = Carbon::now($tz);
        $targetToday = Carbon::parse($targetTime, $tz)->setDate($now->year, $now->month, $now->day);
    } catch (\Throwable $e) {
        return;
    }

    $cacheKey = 'daily_sales_report_last_sent';
    $lastSentDate = Cache::get($cacheKey);

    if ($now->greaterThanOrEqualTo($targetToday) && $lastSentDate !== $now->toDateString()) {
        SendDailySalesReportJob::dispatchSync();
        Cache::forever($cacheKey, $now->toDateString());
    }
})->everyMinute()
    ->timezone(config('app.timezone', 'UTC'))
    ->name('daily-sales-report');
