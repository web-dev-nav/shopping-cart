<?php

namespace App\Jobs;

use App\Mail\DailySalesReportMail;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendDailySalesReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly ?string $date = null, // YYYY-MM-DD in app timezone
    ) {
    }

    public function handle(): void
    {
        $tz = config('app.timezone', 'UTC');

        $day = $this->date
            ? CarbonImmutable::parse($this->date, $tz)->startOfDay()
            : CarbonImmutable::now($tz)->startOfDay();

        $start = $day;
        $end = $day->endOfDay();

        $rows = DB::table('order_items')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->whereBetween('order_items.created_at', [$start, $end])
            ->groupBy('order_items.product_id', 'products.name')
            ->orderBy('products.name')
            ->select([
                'order_items.product_id as product_id',
                'products.name as product_name',
                DB::raw('SUM(order_items.quantity) as quantity_sold'),
                DB::raw('SUM(order_items.price_cents * order_items.quantity) as revenue_cents'),
            ])
            ->get();

        $adminEmail = (string) env('SHOP_ADMIN_EMAIL', 'admin@example.com');

        Mail::to($adminEmail)->send(new DailySalesReportMail(
            reportDate: $day->toDateString(),
            items: $rows->map(fn ($r) => [
                'product_id' => (int) $r->product_id,
                'product_name' => (string) $r->product_name,
                'quantity_sold' => (int) $r->quantity_sold,
                'revenue_cents' => (int) $r->revenue_cents,
            ])->all(),
        ));
    }
}
