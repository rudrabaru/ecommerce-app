<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            // Remove redundant string fields since we now use foreign keys
            $table->dropColumn(['city', 'state', 'country']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            // Add back the string fields if rollback is needed
            $table->string('city')->after('city_id');
            $table->string('state')->after('state_id');
            $table->string('country')->after('country_id');
        });
    }
};
