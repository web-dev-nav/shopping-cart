<?php

namespace App\Jobs;

use App\Mail\LowStockNotificationMail;
use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendLowStockNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $productId,
    ) {
    }

    public function handle(): void
    {
        $product = Product::query()->find($this->productId);

        if (! $product) {
            return;
        }

        $adminEmail = (string) env('SHOP_ADMIN_EMAIL', 'admin@example.com');

        Mail::to($adminEmail)->send(new LowStockNotificationMail(
            productId: $product->id,
            productName: $product->name,
            stockQuantity: $product->stock_quantity,
            lowStockThreshold: (int) env('LOW_STOCK_THRESHOLD', 5),
        ));
    }
}
