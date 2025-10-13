<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up()
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained()->onDelete('cascade');
            $table->string('name', 255);
            $table->timestamps();

            // Add index for performance since user_addresses references city name
            $table->index('name');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cities');
    }
};
