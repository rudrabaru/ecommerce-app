<?php

namespace Modules\Products\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Products\Models\Category;
use Modules\Products\Models\Product;

class SeedCategoriesAndProducts extends Seeder
{
    public function run(): void
    {
        // Create 5 categories and 10 products per category
        Category::factory()->count(5)->create()->each(function (Category $category) {
            Product::factory()->count(10)->create([
                'category_id' => $category->id,
            ]);
        });
    }
}


