<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_otps', function (Blueprint $table) {
            if (!Schema::hasColumn('email_otps', 'link_token')) {
                $table->string('link_token', 64)->nullable()->unique()->after('code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('email_otps', function (Blueprint $table) {
            if (Schema::hasColumn('email_otps', 'link_token')) {
                $table->dropUnique(['link_token']);
                $table->dropColumn('link_token');
            }
        });
    }
};


