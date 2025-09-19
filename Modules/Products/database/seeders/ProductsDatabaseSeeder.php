<?php

namespace Modules\Products\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Products\App\Models\Category;

class ProductsDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Category::count() === 0) {
            Category::create(['name' => 'Electronics']);
            Category::create(['name' => 'Clothing']);
            Category::create(['name' => 'Books']);
        }
    }
}
