<?php

namespace Tests\Feature;

use App\Mail\LowStockNotificationMail;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_add_update_and_remove_cart_items(): void
    {
        $user = User::factory()->create();

        $product = Product::query()->create([
            'name' => 'Test Product',
            'price_cents' => 1500,
            'stock_quantity' => 10,
        ]);

        // Add to cart
        $this->actingAs($user)
            ->post(route('cart.items.store'), [
                'product_id' => $product->id,
                'quantity' => 2,
            ])
            ->assertStatus(303);

        $cart = Cart::query()->where('user_id', $user->id)->firstOrFail();
        $item = CartItem::query()
            ->where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->firstOrFail();

        $this->assertSame(2, $item->quantity);

        // Update quantity
        $this->actingAs($user)
            ->patch(route('cart.items.update', $item), [
                'quantity' => 5,
            ])
            ->assertStatus(303);

        $this->assertSame(5, $item->refresh()->quantity);

        // Remove item
        $this->actingAs($user)
            ->delete(route('cart.items.destroy', $item))
            ->assertStatus(303);

        $this->assertDatabaseMissing('cart_items', [
            'id' => $item->id,
        ]);
    }

    public function test_checkout_creates_order_decrements_stock_and_sends_low_stock_email(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        // threshold default is 5; we'll start at 6 and buy 2 => 4 (low stock)
        $product = Product::query()->create([
            'name' => 'Low Stock Product',
            'price_cents' => 999,
            'stock_quantity' => 6,
        ]);

        $this->actingAs($user)
            ->post(route('cart.items.store'), [
                'product_id' => $product->id,
                'quantity' => 2,
            ])
            ->assertStatus(303);

        $this->actingAs($user)
            ->post(route('checkout.store'))
            ->assertStatus(302);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'total_cents' => 1998,
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'price_cents' => 999,
            'quantity' => 2,
        ]);

        $this->assertSame(4, $product->refresh()->stock_quantity);

        Mail::assertSent(LowStockNotificationMail::class, function (LowStockNotificationMail $mail) use ($product) {
            return $mail->productId === $product->id
                && $mail->productName === $product->name
                && $mail->stockQuantity === 4;
        });
    }
}