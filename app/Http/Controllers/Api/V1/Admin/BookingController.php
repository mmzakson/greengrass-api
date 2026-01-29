<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Booking\BookingListResource;
use App\Http\Resources\Booking\BookingDetailResource;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(
        protected BookingService $bookingService
    ) {}

    /**
     * Get all bookings
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $bookings = $this->bookingService->getAllBookings($perPage);

            return response()->json([
                'success' => true,
                'data' => BookingListResource::collection($bookings->items()),
                'pagination' => [
                    'total' => $bookings->total(),
                    'per_page' => $bookings->perPage(),
                    'current_page' => $bookings->currentPage(),
                    'last_page' => $bookings->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch bookings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single booking
     */
    public function show(string $id): JsonResponse
    {
        try {
            $booking = $this->bookingService->getBookingById($id);

            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => new BookingDetailResource($booking),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch booking',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Confirm booking
     */
    public function confirm(string $id): JsonResponse
    {
        try {
            $booking = $this->bookingService->confirmBooking($id);

            return response()->json([
                'success' => true,
                'message' => 'Booking confirmed successfully',
                'data' => new BookingDetailResource($booking),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Cancel booking (admin)
     */
    public function cancel(string $id, Request $request): JsonResponse
    {
        try {
            $request->validate([
                'cancellation_reason' => 'required|string|max:500',
            ]);

            $booking = $this->bookingService->cancelBooking(
                $id,
                $request->cancellation_reason,
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Booking cancelled successfully',
                'data' => new BookingDetailResource($booking),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update booking notes (admin only)
     */
    public function updateNotes(string $id, Request $request): JsonResponse
    {
        try {
            $request->validate([
                'notes' => 'required|string|max:1000',
            ]);

            $booking = $this->bookingService->getBookingById($id);
            
            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking not found',
                ], 404);
            }

            $booking->update(['notes' => $request->notes]);

            return response()->json([
                'success' => true,
                'message' => 'Notes updated successfully',
                'data' => new BookingDetailResource($booking->fresh()),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}