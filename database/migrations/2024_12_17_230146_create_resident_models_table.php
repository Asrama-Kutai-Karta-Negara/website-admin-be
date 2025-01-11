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
        Schema::create('residents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->unsignedInteger('age');
            $table->date('birth_date');
            $table->string('address');
            $table->foreignUuid('origin_city_id')->constrained('origin_cities')->onDelete('cascade');
            $table->foreignUuid('origin_campus_id')->constrained('origin_campuses')->onDelete('cascade');
            $table->string('phone_number')->nullable();
            $table->foreignUuid('room_number_id')->constrained('room_numbers')->onDelete('cascade');
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('residents');
    }
};
