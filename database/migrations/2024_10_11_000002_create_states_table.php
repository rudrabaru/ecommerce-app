<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->onDelete('cascade');
            $table->string('name', 255);
            $table->string('code', 10)->nullable(); // For state codes like CA, NY, etc.
            $table->timestamps();

            // Add index for performance since user_addresses references state name
            $table->index('name');
        });
    }

    public function down()
    {
        Schema::dropIfExists('states');
    }
};