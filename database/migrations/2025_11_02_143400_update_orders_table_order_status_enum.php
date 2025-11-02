<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Convert order_status from varchar to ENUM
        DB::statement("ALTER TABLE `orders` MODIFY `order_status` ENUM('pending', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert back to varchar
        DB::statement("ALTER TABLE `orders` MODIFY `order_status` VARCHAR(255) NOT NULL DEFAULT 'pending'");
    }
};
