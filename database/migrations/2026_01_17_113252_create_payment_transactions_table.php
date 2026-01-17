<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('transaction_reference')->unique(); // Our internal reference
            $table->uuid('booking_id');
            $table->uuid('user_id')->nullable();
            
            // Payment gateway details
            $table->string('payment_gateway')->default('paystack'); // paystack, flutterwave, etc.
            $table->string('gateway_reference')->nullable(); // Paystack reference
            $table->string('gateway_transaction_id')->nullable(); // Paystack transaction ID
            
            // Transaction details
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10)->default('NGN');
            $table->enum('type', ['full_payment', 'partial_payment', 'deposit', 'refund'])->default('full_payment');
            $table->enum('status', ['pending', 'processing', 'success', 'failed', 'cancelled', 'refunded'])->default('pending');
            
            // Payment method
            $table->string('payment_method')->nullable(); // card, bank_transfer, ussd
            $table->string('card_type')->nullable(); // visa, mastercard, verve
            $table->string('card_last4')->nullable(); // Last 4 digits
            $table->string('bank_name')->nullable();
            
            // Additional information
            $table->text('gateway_response')->nullable(); // JSON response from gateway
            $table->text('metadata')->nullable(); // Additional data
            $table->string('ip_address', 45)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('failure_reason')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('transaction_reference');
            $table->index('gateway_reference');
            $table->index('gateway_transaction_id');
            $table->index('booking_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('payment_gateway');
            $table->index(['booking_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
