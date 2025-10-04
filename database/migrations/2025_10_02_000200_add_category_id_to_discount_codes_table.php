<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('discount_codes') && !Schema::hasColumn('discount_codes', 'category_id')) {
            Schema::table('discount_codes', function (Blueprint $table) {
                $table->unsignedBigInteger('category_id')->nullable()->after('is_active');
                $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('discount_codes', 'category_id')) {
            Schema::table('discount_codes', function (Blueprint $table) {
                $table->dropForeign(['category_id']);
                $table->dropColumn('category_id');
            });
        }
    }
};


