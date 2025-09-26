<?php

namespace Modules\Products\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Products\Models\Category;
use Modules\Products\Models\Product;

class SelectiveCatalogResetSeeder extends Seeder
{
    public function run(): void
    {
        // Disable FKs for MySQL and SQLite
        DB::statement('PRAGMA foreign_keys = OFF');
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        DB::table('orders')->truncate();
        Product::truncate();
        Category::truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        DB::statement('PRAGMA foreign_keys = ON');

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


