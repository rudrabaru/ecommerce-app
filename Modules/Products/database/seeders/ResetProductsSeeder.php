<?php

namespace Modules\Products\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Products\Models\Category;
use Modules\Products\Models\Product;

class ResetProductsSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF'); // sqlite fallback
        DB::statement('SET FOREIGN_KEY_CHECKS=0'); // mysql

        Product::truncate();
        Category::truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        DB::statement('PRAGMA foreign_keys = ON');

        // 5 categories, each with 10 products
        Category::factory()
            ->count(5)
            ->create()
            ->each(function (Category $category) {
                Product::factory()->count(10)->create([
                    'category_id' => $category->id,
                ]);
            });
    }
}


