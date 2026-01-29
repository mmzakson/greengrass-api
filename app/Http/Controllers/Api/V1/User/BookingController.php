<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\CreateBookingRequest;
use App\Http\Requests\Booking\CancelBookingRequest;
use App\Http\Requests\Booking\AddTravelerRequest;
use App\Http\Resources\Booking\BookingListResource;
use App\Http\Resources\Booking\BookingDetailResource;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;

class BookingController extends Controller
{
    public function __construct(
        protected BookingService $bookingService
    ) {}

    /**
     * Get all bookings for authenticated user
     */
    public function index(): JsonResponse
    {
        try {
            $bookings = $this->bookingService->getUserBookings(
                auth()->id(),
                request()->input('per_page', 15)
            );

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
     * Create new booking
     */
    public function store(CreateBookingRequest $request): JsonResponse
    {
        try {
            $userId = auth()->check() ? auth()->id() : null;
            
            $booking = $this->bookingService->createBooking(
                $request->validated(),
                $userId
            );

            return response()->json([
                'success' => true,
                'message' => 'Booking created successfully',
                'data' => new BookingDetailResource($booking),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
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

            // Verify ownership
            if (auth()->check() && $booking->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this booking',
                ], 403);
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
     * Cancel booking
     */
    public function cancel(string $id, CancelBookingRequest $request): JsonResponse
    {
        try {
            $booking = $this->bookingService->getBookingById($id);

            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking not found',
                ], 404);
            }

            // Verify ownership
            if (auth()->check() && $booking->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            $booking = $this->bookingService->cancelBooking(
                $id,
                $request->cancellation_reason
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
     * Add traveler to booking
     */
    public function addTraveler(string $id, AddTravelerRequest $request): JsonResponse
    {
        try {
            $booking = $this->bookingService->getBookingById($id);

            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking not found',
                ], 404);
            }

            // Verify ownership
            if (auth()->check() && $booking->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            $booking = $this->bookingService->addTraveler($id, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Traveler added successfully',
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
     * Get booking by reference (for guests)
     */
    public function getByReference(string $reference): JsonResponse
    {
        try {
            $booking = $this->bookingService->getBookingByReference($reference);

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
}