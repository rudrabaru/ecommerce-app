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
        // Remove 'status' field from orders table (keep only 'order_status')
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('status');
        });
        
        // Update payments status to only allow pending/paid
        // First, update any existing records that don't match our new values
        DB::table('payments')->whereNotIn('status', ['pending', 'paid'])->update(['status' => 'pending']);
        
        // Add check constraint for payments status (if supported by database)
        // Note: This is MySQL specific - adjust for other databases
        DB::statement("ALTER TABLE payments ADD CONSTRAINT chk_payments_status CHECK (status IN ('pending', 'paid'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove check constraint
        DB::statement("ALTER TABLE payments DROP CONSTRAINT chk_payments_status");
        
        // Add back 'status' field to orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('order_status');
        });
    }
};