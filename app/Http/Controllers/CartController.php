<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    // Display cart with items and totals
    public function show(Request $request): Response
    {
        // Get or create cart for current user
        $cart = Cart::firstOrCreate([
            'user_id' => $request->user()->id,
        ]);

        $cart->load(['items.product']);

        return Inertia::render('Cart/Show', [
            'cart' => [
                'id' => $cart->id,
                'items' => $cart->items->map(fn (CartItem $item) => [
                    'id' => $item->id,
                    'quantity' => $item->quantity,
                    'product' => [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'price_cents' => $item->product->price_cents,
                        'stock_quantity' => $item->product->stock_quantity,
                    ],
                    'line_total_cents' => $item->product->price_cents * $item->quantity,
                ]),
                'total_cents' => $cart->items->sum(fn (CartItem $item) => $item->product->price_cents * $item->quantity),
            ],
        ]);
    }

    // Add product to cart or increase quantity
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cart = Cart::firstOrCreate([
            'user_id' => $request->user()->id,
        ]);

        /** @var Product $product */
        $product = Product::query()->findOrFail($data['product_id']);

        // Get existing cart item or create new one
        $item = CartItem::query()->firstOrNew([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
        ]);

        // Calculate new quantity
        $newQuantity = ($item->exists ? $item->quantity : 0) + (int) $data['quantity'];

        // Check stock availability
        if ($newQuantity > $product->stock_quantity) {
            throw ValidationException::withMessages([
                'quantity' => "Only {$product->stock_quantity} left in stock.",
            ]);
        }

        $item->quantity = $newQuantity;
        $item->save();

        return back(303);
    }

    // Update cart item quantity
    public function update(Request $request, CartItem $cartItem): RedirectResponse
    {
        // Verify cart belongs to current user
        if ($cartItem->cart->user_id !== $request->user()->id) {
            abort(403);
        }

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cartItem->loadMissing('product');

        // Check stock availability
        if ((int) $data['quantity'] > $cartItem->product->stock_quantity) {
            throw ValidationException::withMessages([
                'quantity' => "Only {$cartItem->product->stock_quantity} left in stock.",
            ]);
        }

        $cartItem->quantity = (int) $data['quantity'];
        $cartItem->save();

        return back(303);
    }

    // Remove item from cart
    public function destroy(Request $request, CartItem $cartItem): RedirectResponse
    {
        // Verify cart belongs to current user
        if ($cartItem->cart->user_id !== $request->user()->id) {
            abort(403);
        }

        $cartItem->delete();

        return back(303);
    }
}
