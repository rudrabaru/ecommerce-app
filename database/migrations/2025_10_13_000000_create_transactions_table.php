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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('gateway', ['stripe', 'razorpay']);
            $table->string('gateway_payment_id')->nullable()->index();
            $table->string('gateway_order_id')->nullable()->index();
            $table->unsignedBigInteger('amount');
            $table->string('currency', 3);
            $table->enum('status', ['pending', 'authorized', 'paid', 'failed', 'refunded'])->default('pending');
            $table->string('error_code')->nullable();
            $table->string('error_message', 1024)->nullable();
            $table->json('payload')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};


