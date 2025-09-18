<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_otps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('email');
            $table->string('code', 6);
            $table->timestamp('expires_at');
            $table->boolean('used')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'email', 'used']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_otps');
    }
};


