<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $teams = config('permission.teams');

        if (empty($tableNames)) {
            $tableNames = [
                'roles' => 'roles',
                'permissions' => 'permissions',
                'model_has_permissions' => 'model_has_permissions',
                'model_has_roles' => 'model_has_roles',
                'role_has_permissions' => 'role_has_permissions',
            ];
        }

        if (empty($columnNames)) {
            $columnNames = [
                'model_morph_key' => 'model_id',
            ];
        }

        Schema::create($tableNames['permissions'], function (Blueprint $table) use ($teams) {
            $table->bigIncrements('id');
            if ($teams) {
                $table->unsignedBigInteger(PermissionRegistrar::$teamsKey)->nullable();
                $table->index(PermissionRegistrar::$teamsKey, 'permissions_'.PermissionRegistrar::$teamsKey.'_index');
            }
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        Schema::create($tableNames['roles'], function (Blueprint $table) use ($teams) {
            $table->bigIncrements('id');
            if ($teams) {
                $table->unsignedBigInteger(PermissionRegistrar::$teamsKey)->nullable();
                $table->index(PermissionRegistrar::$teamsKey, 'roles_'.PermissionRegistrar::$teamsKey.'_index');
            }
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        Schema::create($tableNames['model_has_permissions'], function (Blueprint $table) use ($tableNames, $columnNames, $teams) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            if ($teams) {
                $table->unsignedBigInteger(PermissionRegistrar::$teamsKey);
                $table->index(PermissionRegistrar::$teamsKey, 'model_has_permissions_'.PermissionRegistrar::$teamsKey.'_index');
            }
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_permissions_model_id_model_type_index');
            $table->foreign('permission_id')
                ->references('id')
                ->on($tableNames['permissions'])
                ->onDelete('cascade');
            $table->primary(array_filter([
                'permission_id',
                $columnNames['model_morph_key'],
                $teams ? PermissionRegistrar::$teamsKey : null,
                'model_type',
            ]), 'model_has_permissions_permission_model_type_primary');
        });

        Schema::create($tableNames['model_has_roles'], function (Blueprint $table) use ($tableNames, $columnNames, $teams) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            if ($teams) {
                $table->unsignedBigInteger(PermissionRegistrar::$teamsKey);
                $table->index(PermissionRegistrar::$teamsKey, 'model_has_roles_'.PermissionRegistrar::$teamsKey.'_index');
            }
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_roles_model_id_model_type_index');
            $table->foreign('role_id')
                ->references('id')
                ->on($tableNames['roles'])
                ->onDelete('cascade');
            $table->primary(array_filter([
                'role_id',
                $columnNames['model_morph_key'],
                $teams ? PermissionRegistrar::$teamsKey : null,
                'model_type',
            ]), 'model_has_roles_role_model_type_primary');
        });

        Schema::create($tableNames['role_has_permissions'], function (Blueprint $table) use ($tableNames) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->foreign('permission_id')
                ->references('id')
                ->on($tableNames['permissions'])
                ->onDelete('cascade');
            $table->foreign('role_id')
                ->references('id')
                ->on($tableNames['roles'])
                ->onDelete('cascade');
            $table->primary([
                'permission_id',
                'role_id',
            ], 'role_has_permissions_permission_id_role_id_primary');
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');
        if (empty($tableNames)) {
            $tableNames = [
                'roles' => 'roles',
                'permissions' => 'permissions',
                'model_has_permissions' => 'model_has_permissions',
                'model_has_roles' => 'model_has_roles',
                'role_has_permissions' => 'role_has_permissions',
            ];
        }

        Schema::dropIfExists($tableNames['role_has_permissions']);
        Schema::dropIfExists($tableNames['model_has_roles']);
        Schema::dropIfExists($tableNames['model_has_permissions']);
        Schema::dropIfExists($tableNames['roles']);
        Schema::dropIfExists($tableNames['permissions']);
    }
};


