<?php

namespace App\Http\Controllers;

use App\Jobs\SendLowStockNotificationJob;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $userId = $request->user()->id;
        $lowStockThreshold = (int) env('LOW_STOCK_THRESHOLD', 5);

        DB::transaction(function () use ($userId, $lowStockThreshold) {
            $cart = Cart::query()
                ->where('user_id', $userId)
                ->with(['items.product'])
                ->first();

            if (! $cart || $cart->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'cart' => 'Your cart is empty.',
                ]);
            }

            $order = Order::query()->create([
                'user_id' => $userId,
                'total_cents' => 0,
            ]);

            $totalCents = 0;

            foreach ($cart->items as $item) {
                /** @var Product $product */
                $product = Product::query()
                    ->whereKey($item->product_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($item->quantity > $product->stock_quantity) {
                    throw ValidationException::withMessages([
                        'cart' => "Not enough stock for {$product->name}. Only {$product->stock_quantity} left.",
                    ]);
                }

                $newStock = $product->stock_quantity - $item->quantity;

                $product->stock_quantity = $newStock;
                $product->save();

                $lineTotal = $product->price_cents * $item->quantity;
                $totalCents += $lineTotal;

                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'price_cents' => $product->price_cents,
                    'quantity' => $item->quantity,
                ]);

                if ($newStock <= $lowStockThreshold) {
                    SendLowStockNotificationJob::dispatch($product->id)->afterCommit();
                }
            }

            $order->total_cents = $totalCents;
            $order->save();

            $cart->items()->delete();
        });

        return redirect()
            ->route('cart.show')
            ->with('success', 'Order placed successfully.');
    }
}
