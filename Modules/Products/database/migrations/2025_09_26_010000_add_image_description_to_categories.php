<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'image')) {
                $table->string('image')->nullable()->after('parent_id');
            }
            if (!Schema::hasColumn('categories', 'description')) {
                $table->text('description')->nullable()->after('image');
            }
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('categories', 'image')) {
                $table->dropColumn('image');
            }
        });
    }
};
