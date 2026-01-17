<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_availability', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('travel_package_id');
            $table->date('date');
            $table->integer('available_slots');
            $table->integer('booked_slots')->default(0);
            $table->boolean('is_available')->default(true);
            $table->decimal('price_override', 12, 2)->nullable(); // Special pricing for specific dates
            $table->text('notes')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('travel_package_id')->references('id')->on('travel_packages')->onDelete('cascade');

            // Indexes
            $table->index('travel_package_id');
            $table->index('date');
            $table->index('is_available');
            $table->unique(['travel_package_id', 'date']); // One record per package per date
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_availability');
    }
};
