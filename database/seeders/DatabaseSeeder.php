<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Optionally create random users if needed
        // User::factory(10)->create();

        if (Schema::hasTable('roles')) {
            $this->call([
                RoleSeeder::class,
                UsersWithRolesSeeder::class,
            ]);
        }

        // Seed categories and products (5 x 10)
        \Artisan::call('module:seed', [
            'module' => 'Products',
            '--class' => 'Modules\\Products\\Database\\Seeders\\SeedCategoriesAndProducts'
        ]);

        // Note: Do not modify catalog data or orders here.
    }
}
