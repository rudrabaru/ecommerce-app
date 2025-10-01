<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $userRoleId = Role::where('name', 'user')->value('id');
        if ($userRoleId) {
            // Set verified where email_verified_at is set
            DB::table('users')
                ->where('role_id', $userRoleId)
                ->whereNotNull('email_verified_at')
                ->update(['status' => 'verified']);
            // Set unverified where email_verified_at is null and status currently null
            DB::table('users')
                ->where('role_id', $userRoleId)
                ->whereNull('email_verified_at')
                ->whereNull('status')
                ->update(['status' => 'unverified']);
        }
        // Ensure non-user roles have status null
        if ($userRoleId) {
            DB::table('users')
                ->where('role_id', '!=', $userRoleId)
                ->update(['status' => null]);
        }
    }

    public function down(): void
    {
        // Revert statuses to null to keep migration reversible
        DB::table('users')->update(['status' => null]);
    }
};
