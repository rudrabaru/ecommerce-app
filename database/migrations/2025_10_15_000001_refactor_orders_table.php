<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Ensure order_number exists and is unique
            if (! Schema::hasColumn('orders', 'order_number')) {
                $table->string('order_number')->unique()->after('id');
            }

            // Convert status to a flexible string to allow values like paid/failed
            if (Schema::hasColumn('orders', 'status')) {
                $table->string('status')->default('pending')->change();
            } else {
                $table->string('status')->default('pending');
            }

            // Add order-level fields if missing
            if (! Schema::hasColumn('orders', 'user_id')) {
                $table->unsignedBigInteger('user_id')->after('order_number');
            }
            if (! Schema::hasColumn('orders', 'total_amount')) {
                $table->decimal('total_amount', 10, 2)->default(0)->after('user_id');
            }
            if (! Schema::hasColumn('orders', 'shipping_address')) {
                $table->text('shipping_address')->nullable()->after('status');
            }
            if (! Schema::hasColumn('orders', 'shipping_address_id')) {
                $table->unsignedBigInteger('shipping_address_id')->nullable()->after('shipping_address');
            }
            if (! Schema::hasColumn('orders', 'payment_method_id')) {
                $table->unsignedBigInteger('payment_method_id')->nullable()->after('shipping_address_id');
            }
            if (! Schema::hasColumn('orders', 'notes')) {
                $table->text('notes')->nullable()->after('payment_method_id');
            }
            if (! Schema::hasColumn('orders', 'discount_code')) {
                $table->string('discount_code')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('orders', 'discount_amount')) {
                $table->decimal('discount_amount', 10, 2)->default(0)->after('discount_code');
            }

            // Drop any per-product fields that shouldn't live on orders
            // First attempt to drop foreign keys if they exist
            if (Schema::hasColumn('orders', 'provider_id')) {
                try { $table->dropForeign(['provider_id']); } catch (\Throwable $e) {}
            }
            if (Schema::hasColumn('orders', 'product_id')) {
                try { $table->dropForeign(['product_id']); } catch (\Throwable $e) {}
            }
            // Now drop columns
            if (Schema::hasColumn('orders', 'provider_id')) { $table->dropColumn('provider_id'); }
            if (Schema::hasColumn('orders', 'product_id')) { $table->dropColumn('product_id'); }
            if (Schema::hasColumn('orders', 'quantity')) { $table->dropColumn('quantity'); }
            if (Schema::hasColumn('orders', 'unit_price')) { $table->dropColumn('unit_price'); }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Best-effort rollback: keep columns that are safe to drop
            if (Schema::hasColumn('orders', 'discount_amount')) {
                $table->dropColumn('discount_amount');
            }
            if (Schema::hasColumn('orders', 'discount_code')) {
                $table->dropColumn('discount_code');
            }
            if (Schema::hasColumn('orders', 'notes')) {
                $table->dropColumn('notes');
            }
            if (Schema::hasColumn('orders', 'payment_method_id')) {
                $table->dropColumn('payment_method_id');
            }
            if (Schema::hasColumn('orders', 'shipping_address_id')) {
                $table->dropColumn('shipping_address_id');
            }
            if (Schema::hasColumn('orders', 'shipping_address')) {
                $table->dropColumn('shipping_address');
            }
            // Revert status back to nullable string (cannot restore enum automatically)
            if (Schema::hasColumn('orders', 'status')) {
                $table->string('status')->nullable()->change();
            }
        });
    }
};


