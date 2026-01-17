<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travelers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('booking_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('passport_number')->nullable();
            $table->date('passport_expiry')->nullable();
            $table->string('passport_copy')->nullable(); // File path
            $table->string('nationality', 100)->nullable();
            $table->enum('traveler_type', ['adult', 'child', 'infant'])->default('adult');
            $table->text('special_needs')->nullable(); // Dietary, medical, etc.
            $table->text('emergency_contact')->nullable(); // JSON: {name, phone, relationship}
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');

            // Indexes
            $table->index('booking_id');
            $table->index('passport_number');
            $table->index('traveler_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travelers');
    }
};
