<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_packages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->text('highlights')->nullable(); // JSON array of highlights
            $table->text('inclusions')->nullable(); // JSON array of what's included
            $table->text('exclusions')->nullable(); // JSON array of what's not included
            $table->string('destination');
            $table->string('country', 100);
            $table->integer('duration_days');
            $table->integer('duration_nights');
            $table->decimal('price', 12, 2); // Base price per person
            $table->decimal('child_price', 12, 2)->nullable(); // Price for children
            $table->integer('max_travelers')->default(50);
            $table->integer('min_travelers')->default(1);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('type', ['group', 'private', 'custom'])->default('group');
            $table->enum('category', ['adventure', 'luxury', 'budget', 'cultural', 'religious', 'beach', 'safari'])->default('adventure');
            $table->enum('difficulty_level', ['easy', 'moderate', 'challenging'])->default('easy');
            $table->text('itinerary')->nullable(); // JSON array of day-by-day itinerary
            $table->text('images')->nullable(); // JSON array of image URLs
            $table->string('featured_image')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('available_slots')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('created_by')->references('id')->on('admins')->onDelete('set null');

            // Indexes for search and filtering
            $table->index('slug');
            $table->index('destination');
            $table->index('country');
            $table->index('type');
            $table->index('category');
            $table->index('is_featured');
            $table->index('is_active');
            $table->index('start_date');
            $table->index('end_date');
            $table->index(['price', 'is_active']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_packages');
    }
};
