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
            Role::firstOrCreate(['name' => $roleName], ['guard_name' => 'web']);
        }

        // Assign roles based on user id using Spatie pivot (idempotent)
        $admin = User::find(1);
        if ($admin) {
            $admin->syncRoles(['admin']);
            $adminRoleId = Role::where('name', 'admin')->value('id');
            if ($adminRoleId) {
                $admin->role_id = $adminRoleId;
            }
            $admin->save();
        }

        $provider = User::find(2);
        if ($provider && $provider->id !== 1) {
            $provider->syncRoles(['provider']);
            $providerRoleId = Role::where('name', 'provider')->value('id');
            if ($providerRoleId) {
                $provider->role_id = $providerRoleId;
            }
            $provider->save();
        }

        // Ensure all other users have role user
        $userRoleId = Role::where('name', 'user')->value('id');
        User::whereNotIn('id', [1,2])->get()->each(function (User $u) use ($userRoleId) {
            $u->syncRoles(['user']);
            if ($userRoleId) {
                $u->role_id = $userRoleId;
            }
            $u->save();
        });
    }
}
