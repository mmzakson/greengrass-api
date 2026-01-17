<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('travel_package_id');
            $table->uuid('booking_id')->nullable(); // Link to actual booking
            $table->tinyInteger('rating')->unsigned(); // 1-5
            $table->string('title')->nullable();
            $table->text('comment');
            $table->text('images')->nullable(); // JSON array of review images
            $table->boolean('is_verified')->default(false); // Verified purchase
            $table->boolean('is_approved')->default(false); // Admin approval
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('travel_package_id')->references('id')->on('travel_packages')->onDelete('cascade');
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('admins')->onDelete('set null');

            // Indexes
            $table->index('user_id');
            $table->index('travel_package_id');
            $table->index('booking_id');
            $table->index('rating');
            $table->index('is_approved');
            $table->index('is_verified');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
