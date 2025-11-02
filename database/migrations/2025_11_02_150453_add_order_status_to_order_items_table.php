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
        Schema::table('order_items', function (Blueprint $table) {
            $table->enum('order_status', ['pending', 'shipped', 'delivered', 'cancelled'])
                  ->default('pending')
                  ->after('total');
        });
        
        // Set initial status based on parent order status
        DB::statement("
            UPDATE order_items 
            SET order_status = (
                SELECT order_status FROM orders WHERE orders.id = order_items.order_id
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('order_status');
        });
    }
};
