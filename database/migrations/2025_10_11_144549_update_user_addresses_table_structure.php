<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            // Add new foreign key columns
            $table->foreignId('country_id')->nullable()->after('address_line_2');
            $table->foreignId('state_id')->nullable()->after('country_id');
            $table->foreignId('city_id')->nullable()->after('state_id');
            $table->string('email')->nullable()->after('country_code');

            // Add foreign key constraints
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('cascade');
            $table->foreign('state_id')->references('id')->on('states')->onDelete('cascade');
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            // Drop foreign key constraints
            $table->dropForeign(['country_id']);
            $table->dropForeign(['state_id']);
            $table->dropForeign(['city_id']);

            // Drop columns
            $table->dropColumn(['country_id', 'state_id', 'city_id', 'email']);
        });
    }
};
