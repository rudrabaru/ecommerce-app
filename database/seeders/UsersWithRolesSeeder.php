<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UsersWithRolesSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure roles exist
        $roles = ['admin', 'provider', 'user'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        // Seed default users with credentials
        $defaults = [
            // 1 admin
            [ 'name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'Admin@123', 'role' => 'admin' ],
            // 2 providers
            [ 'name' => 'Provider One', 'email' => 'provider1@example.com', 'password' => 'Provider@123', 'role' => 'provider' ],
            [ 'name' => 'Provider Two', 'email' => 'provider2@example.com', 'password' => 'Provider@123', 'role' => 'provider' ],
            // 3 users
            [ 'name' => 'User One', 'email' => 'user1@example.com', 'password' => 'User@123', 'role' => 'user' ],
            [ 'name' => 'User Two', 'email' => 'user2@example.com', 'password' => 'User@123', 'role' => 'user' ],
            [ 'name' => 'User Three', 'email' => 'user3@example.com', 'password' => 'User@123', 'role' => 'user' ],
        ];

        foreach ($defaults as $d) {
            $user = User::firstOrCreate(
                ['email' => $d['email']],
                [
                    'name' => $d['name'],
                    'password' => Hash::make($d['password']),
                ]
            );

            if (! $user->hasRole($d['role'])) {
                $user->assignRole($d['role']);
            }
        }
    }
}


