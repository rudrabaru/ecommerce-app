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
        Schema::create('product_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_item_id')->constrained('order_items')->onDelete('cascade');
            $table->unsignedTinyInteger('rating')->nullable()->comment('1-5 stars, optional');
            $table->text('review')->nullable()->comment('Review text, optional');
            $table->timestamps();
            
            // Ensure one rating per (user, product, order) combination
            $table->unique(['user_id', 'product_id', 'order_id'], 'unique_user_product_order_rating');
            
            // Indexes for performance
            $table->index('product_id');
            $table->index('user_id');
            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_ratings');
    }
};
