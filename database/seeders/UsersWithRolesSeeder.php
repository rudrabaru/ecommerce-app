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
            Role::firstOrCreate(['name' => $roleName], ['guard_name' => 'web']);
        }

        // Seed default users with credentials
        $defaults = [
            // 1 admin
            [ 'name' => 'Admin', 'email' => 'gillydanda55@gmail.com', 'password' => 'Admin@123', 'role' => 'admin' ],
            // 2 providers
            [ 'name' => 'Provider One', 'email' => 'rudrabaruwala123@gmail.com', 'password' => 'Provider@123', 'role' => 'provider' ],
            [ 'name' => 'Provider Two', 'email' => 'jainhet782@gmail.com', 'password' => 'Provider@123', 'role' => 'provider' ],
            // 3 users
            [ 'name' => 'User One', 'email' => 'rudrabaruwala,aids23@gmail.com', 'password' => 'User@123', 'role' => 'user' ],
            [ 'name' => 'User Two', 'email' => 'royrudra3011@gmail.com', 'password' => 'User@123', 'role' => 'user' ],
            [ 'name' => 'User Three', 'email' => 'rudrasingh6463@gmail.com', 'password' => 'User@123', 'role' => 'user' ],
        ];

        foreach ($defaults as $d) {
            $user = User::firstOrCreate(
                ['email' => $d['email']],
                [
                    'name' => $d['name'],
                    'password' => Hash::make($d['password']),
                ]
            );

            // Assign spatie role
            if (! $user->hasRole($d['role'])) {
                $user->assignRole($d['role']);
            }

            // Sync role_id column to match spatie roles.id
            $roleId = Role::where('name', $d['role'])->value('id');
            if ($roleId && (int)$user->role_id !== (int)$roleId) {
                $user->role_id = $roleId;
            }

            // Seeded users should be verified by default
            if (is_null($user->email_verified_at)) {
                $user->email_verified_at = now();
            }

            // Optional status column in sync with verification
            if (property_exists($user, 'status') || \Schema::hasColumn('users','status')) {
                $user->status = 'verified';
            }

            $user->save();
        }
    }
}


