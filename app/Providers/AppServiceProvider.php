<?php

namespace App\Providers;

use App\Jobs\SendDailySalesReportJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Lightweight, request-driven daily sales trigger (avoids relying solely on the scheduler being up).
        if (! $this->app->runningInConsole()) {
            $this->maybeDispatchDailySalesReport();
        }
    }

    /**
     * Dispatch the daily sales report once per day after the configured time.
     */
    protected function maybeDispatchDailySalesReport(): void
    {
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
            // Run inline so the report is sent even when no queue worker is running.
            SendDailySalesReportJob::dispatchSync();
            Cache::forever($cacheKey, $now->toDateString());
        }
    }
}
