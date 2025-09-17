<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles idempotently
        $roles = ['admin', 'provider', 'user'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        // Assign roles based on user id using Spatie pivot (idempotent)
        $admin = User::find(1);
        if ($admin) {
            $admin->syncRoles(['admin']);
            if ($admin->role !== 'admin') {
                $admin->role = 'admin';
                $admin->save();
            }
        }

        $provider = User::find(2);
        if ($provider && $provider->id !== 1) {
            $provider->syncRoles(['provider']);
            if ($provider->role !== 'provider') {
                $provider->role = 'provider';
                $provider->save();
            }
        }

        // Ensure all other users have role user
        User::whereNotIn('id', [1,2])->get()->each(function (User $u) {
            $u->syncRoles(['user']);
            if ($u->role !== 'user') {
                $u->role = 'user';
                $u->save();
            }
        });
    }
}
