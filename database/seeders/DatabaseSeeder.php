<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Optionally create random users if needed
        // User::factory(10)->create();

        $this->call([
            RoleSeeder::class,
            UsersWithRolesSeeder::class,
        ]);
    }
}
