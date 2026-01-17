<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('booking_reference')->unique(); // e.g., BLT-20240115-ABCD
            $table->uuid('user_id')->nullable(); // Nullable for guest bookings
            $table->uuid('travel_package_id');
            
            // Guest booking information (if user_id is null)
            $table->string('guest_email')->nullable();
            $table->string('guest_phone', 20)->nullable();
            $table->string('guest_first_name')->nullable();
            $table->string('guest_last_name')->nullable();
            
            // Booking details
            $table->integer('number_of_travelers');
            $table->integer('number_of_adults')->default(0);
            $table->integer('number_of_children')->default(0);
            $table->date('travel_date');
            $table->decimal('total_amount', 12, 2);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('amount_due', 12, 2)->default(0);
            $table->enum('payment_status', ['pending', 'partial', 'paid', 'failed', 'refunded'])->default('pending');
            $table->enum('booking_status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            
            // Additional information
            $table->text('special_requests')->nullable();
            $table->text('notes')->nullable(); // Admin notes
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->uuid('cancelled_by')->nullable(); // Admin who cancelled
            
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('travel_package_id')->references('id')->on('travel_packages')->onDelete('cascade');
            $table->foreign('cancelled_by')->references('id')->on('admins')->onDelete('set null');

            // Indexes for queries and reporting
            $table->index('booking_reference');
            $table->index('user_id');
            $table->index('travel_package_id');
            $table->index('guest_email');
            $table->index('travel_date');
            $table->index('payment_status');
            $table->index('booking_status');
            $table->index(['booking_status', 'payment_status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
