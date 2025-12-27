<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Dummy Admin',
            'email' => 'admin@example.com',
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        Product::query()->insert([
            [
                'name' => 'T-Shirt',
                'price_cents' => 1999,
                'stock_quantity' => 25,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Coffee Mug',
                'price_cents' => 1299,
                'stock_quantity' => 12,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sticker Pack',
                'price_cents' => 499,
                'stock_quantity' => 50,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Hoodie',
                'price_cents' => 4999,
                'stock_quantity' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Notebook',
                'price_cents' => 999,
                'stock_quantity' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
