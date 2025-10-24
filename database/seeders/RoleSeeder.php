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

        // Normalize role_id for all users according to their spatie role
        $adminRoleId = Role::where('name', 'admin')->value('id');
        $providerRoleId = Role::where('name', 'provider')->value('id');
        $userRoleId = Role::where('name', 'user')->value('id');

        User::with('roles')->get()->each(function (User $u) use ($adminRoleId, $providerRoleId, $userRoleId) {
            $roleName = optional($u->roles->first())->name;
            $targetRoleId = null;
            if ($roleName === 'admin') {
                $targetRoleId = $adminRoleId;
            } elseif ($roleName === 'provider') {
                $targetRoleId = $providerRoleId;
            } else {
                $targetRoleId = $userRoleId;
            }

            if ($targetRoleId && (int)$u->role_id !== (int)$targetRoleId) {
                $u->role_id = $targetRoleId;
            }

            // Ensure all seeded users and normal users are verified
            if (is_null($u->email_verified_at)) {
                $u->email_verified_at = now();
            }

            if (\Schema::hasColumn('users', 'status')) {
                $u->status = 'verified';
            }

            $u->save();
        });
    }
}
