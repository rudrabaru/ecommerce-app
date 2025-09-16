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
            [
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => 'Admin@123',
                'role' => 'admin',
            ],
            [
                'name' => 'Provider',
                'email' => 'provider@example.com',
                'password' => 'Provider@123',
                'role' => 'provider',
            ],
            [
                'name' => 'User',
                'email' => 'user@example.com',
                'password' => 'User@123',
                'role' => 'user',
            ],
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


