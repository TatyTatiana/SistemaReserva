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
        Schema::create('places', function (Blueprint $table) {
            $table->id();

            $table->string('code');
            $table->integer('capacity');
            $table->integer('floor');
            $table->boolean('active')->default(true);

            $table->foreignId('type_id')->references('id')->on('types');
            $table->foreignId('building_id')->references('id')->on('buildings');
            $table->foreignId('seat_id')->references('id')->on('seats');
            $table->foreignId('user_id')->references('id')->on('users');

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('places');
    }
};
