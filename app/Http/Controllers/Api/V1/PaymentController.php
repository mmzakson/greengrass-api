<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * Initialize payment for booking
     */
    public function initializePayment(Request $request, string $bookingId): JsonResponse
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:100'],
            'type' => ['nullable', 'in:full_payment,partial_payment,deposit'],
        ]);

        try {
            $booking = Booking::findOrFail($bookingId);

            // Verify booking ownership (if authenticated)
            if (auth()->check() && $booking->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this booking',
                ], 403);
            }

            // Check if booking is already paid
            if ($booking->payment_status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'This booking is already fully paid',
                ], 400);
            }

            // Check if booking is cancelled
            if ($booking->booking_status === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot pay for a cancelled booking',
                ], 400);
            }

            $result = $this->paymentService->initializePayment(
                $booking,
                $request->amount,
                auth()->id(),
                $request->type ?? 'full_payment'
            );

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Verify payment
     */
    public function verifyPayment(string $reference): JsonResponse
    {
        try {
            $paymentData = $this->paymentService->verifyPayment($reference);
            $transaction = $this->paymentService->getTransactionByReference($reference);

            return response()->json([
                'success' => true,
                'message' => 'Payment verified successfully',
                'data' => [
                    'transaction' => $transaction,
                    'payment_details' => $paymentData,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get booking payment history
     */
    public function getBookingPayments(string $bookingId): JsonResponse
    {
        try {
            $booking = Booking::findOrFail($bookingId);

            // Verify ownership
            if (auth()->check() && $booking->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            $transactions = $this->paymentService->getBookingTransactions($bookingId);

            return response()->json([
                'success' => true,
                'data' => [
                    'booking' => [
                        'id' => $booking->id,
                        'reference' => $booking->booking_reference,
                        'total_amount' => $booking->total_amount,
                        'amount_paid' => $booking->amount_paid,
                        'amount_due' => $booking->amount_due,
                        'payment_status' => $booking->payment_status,
                    ],
                    'transactions' => $transactions,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculate payment with fees
     */
    public function calculatePayment(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:100'],
        ]);

        try {
            $breakdown = $this->paymentService->calculateProcessingFee($request->amount);

            return response()->json([
                'success' => true,
                'data' => $breakdown,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}