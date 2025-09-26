<?php

namespace Modules\Products\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Products\Models\Category;
use Modules\Products\Models\Product;
use App\Models\User;

class DemoProductsSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure we have a provider user
        $providerId = User::query()->whereHas('roles', function($q){ $q->where('name', 'provider'); })->value('id');
        if (!$providerId) {
            $provider = User::query()->first();
            $providerId = $provider?->id ?? 1;
        }

        $categories = [
            'Electronics' => [
                ['Smartphone X', 'Latest-gen smartphone with OLED display.'],
                ['Wireless Headphones', 'Noise-cancelling over-ear headphones.'],
                ['Bluetooth Speaker', 'Portable speaker with deep bass.'],
                ['Smart Watch', 'Fitness tracking and notifications.'],
            ],
            'Clothing' => [
                ['Classic White Tee', 'Premium cotton, regular fit.'],
                ['Denim Jacket', 'Timeless design, durable fabric.'],
                ['Black Jeans', 'Slim fit stretch denim.'],
                ['Running Shoes', 'Lightweight breathable mesh.'],
            ],
            'Books' => [
                ['Laravel in Action', 'Build modern PHP apps with Laravel.'],
                ['Mastering PHP', 'Advanced patterns and practices.'],
                ['Clean Code', 'Handbook of Agile Software Craftsmanship.'],
                ['Design Patterns', 'Elements of Reusable OO Software.'],
            ],
        ];

        foreach ($categories as $categoryName => $products) {
            $category = Category::firstOrCreate(['name' => $categoryName]);

            foreach ($products as [$title, $description]) {
                Product::firstOrCreate(
                    ['title' => $title, 'category_id' => $category->id],
                    [
                        'description' => $description,
                        'price' => rand(1000, 9999) / 100,
                        'stock' => rand(10, 50),
                        'provider_id' => $providerId,
                        'is_approved' => true,
                        'image' => null,
                    ]
                );
            }
        }
    }
}


