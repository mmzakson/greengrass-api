<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    protected string $paystackSecretKey;
    protected string $paystackPublicKey;
    protected string $paystackBaseUrl;

    public function __construct()
    {
        $this->paystackSecretKey = config('paystack.secretKey');
        $this->paystackPublicKey = config('paystack.publicKey');
        $this->paystackBaseUrl = config('paystack.paymentUrl', 'https://api.paystack.co');
    }

    /**
     * Initialize Paystack payment
     */
    public function initializePayment(
        Booking $booking,
        float $amount,
        ?string $userId = null,
        string $type = 'full_payment',
        array $metadata = []
    ): array {
        return DB::transaction(function () use ($booking, $amount, $userId, $type, $metadata) {
            
            // Validate amount
            if ($amount <= 0) {
                throw new \Exception('Invalid payment amount');
            }

            if ($amount > $booking->amount_due) {
                throw new \Exception('Payment amount exceeds amount due');
            }

            // Create transaction record
            $transaction = PaymentTransaction::create([
                'booking_id' => $booking->id,
                'user_id' => $userId,
                'payment_gateway' => 'paystack',
                'amount' => $amount,
                'currency' => 'NGN',
                'type' => $type,
                'status' => 'pending',
                'ip_address' => request()->ip(),
                'metadata' => array_merge($metadata, [
                    'booking_reference' => $booking->booking_reference,
                    'package_title' => $booking->travelPackage->title,
                ]),
            ]);

            // Initialize Paystack payment
            $email = $booking->customer_email;
            $amountInKobo = $amount * 100; // Convert to kobo

            $paystackData = [
                'email' => $email,
                'amount' => $amountInKobo,
                'reference' => $transaction->transaction_reference,
                'currency' => 'NGN',
                'callback_url' => config('app.frontend_url') . '/payment/callback',
                'metadata' => [
                    'booking_id' => $booking->id,
                    'booking_reference' => $booking->booking_reference,
                    'transaction_id' => $transaction->id,
                    'customer_name' => $booking->customer_name,
                    'package_title' => $booking->travelPackage->title,
                ],
            ];

            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->paystackSecretKey,
                    'Content-Type' => 'application/json',
                ])->post($this->paystackBaseUrl . '/transaction/initialize', $paystackData);

                $result = $response->json();

                if (!$response->successful() || !($result['status'] ?? false)) {
                    throw new \Exception($result['message'] ?? 'Failed to initialize payment');
                }

                // Update transaction with Paystack reference
                $transaction->update([
                    'gateway_reference' => $result['data']['reference'],
                    'gateway_response' => $result,
                ]);

                Log::info('Payment initialized', [
                    'transaction_id' => $transaction->id,
                    'booking_id' => $booking->id,
                    'amount' => $amount,
                ]);

                return [
                    'status' => true,
                    'message' => 'Payment initialized successfully',
                    'data' => [
                        'authorization_url' => $result['data']['authorization_url'],
                        'access_code' => $result['data']['access_code'],
                        'reference' => $result['data']['reference'],
                        'transaction_id' => $transaction->id,
                        'transaction_reference' => $transaction->transaction_reference,
                    ],
                ];

            } catch (\Exception $e) {
                // Mark transaction as failed
                $transaction->update([
                    'status' => 'failed',
                    'failed_at' => now(),
                    'failure_reason' => $e->getMessage(),
                ]);

                throw $e;
            }
        });
    }

    /**
     * Verify Paystack payment
     */
    public function verifyPayment(string $reference): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->paystackSecretKey,
            ])->get($this->paystackBaseUrl . "/transaction/verify/{$reference}");

            $result = $response->json();

            if (!$response->successful() || !($result['status'] ?? false)) {
                throw new \Exception($result['message'] ?? 'Payment verification failed');
            }

            return $result['data'];

        } catch (\Exception $e) {
            Log::error('Payment verification failed', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle Paystack webhook
     */
    public function handleWebhook(array $payload): bool
    {
        return DB::transaction(function () use ($payload) {
            
            $event = $payload['event'] ?? null;
            $data = $payload['data'] ?? [];

            Log::info('Paystack webhook received', [
                'event' => $event,
                'reference' => $data['reference'] ?? null,
            ]);

            // Handle charge.success event
            if ($event === 'charge.success') {
                return $this->handleSuccessfulPayment($data);
            }

            // Handle charge.failed event
            if ($event === 'charge.failed') {
                return $this->handleFailedPayment($data);
            }

            return true;
        });
    }

    /**
     * Handle successful payment
     */
    protected function handleSuccessfulPayment(array $data): bool
    {
        $reference = $data['reference'] ?? null;

        if (!$reference) {
            throw new \Exception('Payment reference not found');
        }

        // Find transaction
        $transaction = PaymentTransaction::where('gateway_reference', $reference)
            ->orWhere('transaction_reference', $reference)
            ->first();

        if (!$transaction) {
            Log::warning('Transaction not found for reference', ['reference' => $reference]);
            return false;
        }

        // Prevent duplicate processing
        if ($transaction->status === 'success') {
            Log::info('Payment already processed', ['transaction_id' => $transaction->id]);
            return true;
        }

        // Update transaction
        $transaction->update([
            'status' => 'success',
            'gateway_transaction_id' => $data['id'] ?? null,
            'payment_method' => $data['channel'] ?? null,
            'card_type' => $data['authorization']['card_type'] ?? null,
            'card_last4' => $data['authorization']['last4'] ?? null,
            'bank_name' => $data['authorization']['bank'] ?? null,
            'gateway_response' => $data,
            'paid_at' => now(),
        ]);

        // Update booking payment status
        $booking = $transaction->booking;
        $newAmountPaid = $booking->amount_paid + $transaction->amount;
        $newAmountDue = $booking->total_amount - $newAmountPaid;

        $paymentStatus = 'pending';
        if ($newAmountPaid >= $booking->total_amount) {
            $paymentStatus = 'paid';
        } elseif ($newAmountPaid > 0) {
            $paymentStatus = 'partial';
        }

        $booking->update([
            'amount_paid' => $newAmountPaid,
            'amount_due' => max(0, $newAmountDue),
            'payment_status' => $paymentStatus,
        ]);

        Log::info('Payment processed successfully', [
            'transaction_id' => $transaction->id,
            'booking_id' => $booking->id,
            'amount' => $transaction->amount,
            'payment_status' => $paymentStatus,
        ]);

        // TODO: Send payment confirmation email

        return true;
    }

    /**
     * Handle failed payment
     */
    protected function handleFailedPayment(array $data): bool
    {
        $reference = $data['reference'] ?? null;

        if (!$reference) {
            return false;
        }

        $transaction = PaymentTransaction::where('gateway_reference', $reference)
            ->orWhere('transaction_reference', $reference)
            ->first();

        if (!$transaction) {
            return false;
        }

        $transaction->update([
            'status' => 'failed',
            'failed_at' => now(),
            'failure_reason' => $data['gateway_response'] ?? 'Payment failed',
            'gateway_response' => $data,
        ]);

        Log::info('Payment marked as failed', [
            'transaction_id' => $transaction->id,
            'reason' => $data['gateway_response'] ?? 'Unknown',
        ]);

        return true;
    }

    /**
     * Get transaction by reference
     */
    public function getTransactionByReference(string $reference): ?PaymentTransaction
    {
        return PaymentTransaction::where('transaction_reference', $reference)
            ->orWhere('gateway_reference', $reference)
            ->first();
    }

    /**
     * Get booking transactions
     */
    public function getBookingTransactions(string $bookingId)
    {
        return PaymentTransaction::where('booking_id', $bookingId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Calculate processing fee (if applicable)
     */
    public function calculateProcessingFee(float $amount): array
    {
        // Paystack charges 1.5% + NGN 100 (capped at NGN 2000)
        $percentage = 0.015;
        $flatFee = 100;
        $cap = 2000;

        $fee = min(($amount * $percentage) + $flatFee, $cap);

        return [
            'amount' => $amount,
            'fee' => round($fee, 2),
            'total' => round($amount + $fee, 2),
        ];
    }
}