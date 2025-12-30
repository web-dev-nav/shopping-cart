<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    // Display paginated list of products
    public function index(Request $request): Response
    {
        // Get products ordered by name, paginated 12 per page
        $products = Product::query()
            ->orderBy('name')
            ->paginate(12)
            ->through(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'price_cents' => $product->price_cents,
                'stock_quantity' => $product->stock_quantity,
            ]);

        return Inertia::render('Products/Index', [
            'products' => $products,
        ]);
    }
}
