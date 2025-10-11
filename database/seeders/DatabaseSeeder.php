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
        // if (Schema::hasTable('roles')) {
        //     $this->call([
        //         RoleSeeder::class,
        //         UsersWithRolesSeeder::class,
        //         PaymentMethodSeeder::class,
        //     ]);
        // }

        // // Seed demo catalog last so providers/users are present first
        // $this->call(\Modules\Products\Database\Seeders\HierarchicalProductsSeeder::class);
    }
}
