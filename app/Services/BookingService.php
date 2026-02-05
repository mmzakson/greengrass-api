<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\TravelPackage;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingService
{
    public function __construct(
        protected BookingRepositoryInterface $bookingRepository
    ) {}

    /**
     * Create a new booking with group support
     */
    public function createBooking(array $data, ?string $userId = null): Booking
    {
        return DB::transaction(function () use ($data, $userId) {
            try {
                // Get package
                $package = TravelPackage::findOrFail($data['travel_package_id']);

                // Validate package is active and available
                $this->validatePackageAvailability($package, $data);

                // Calculate totals
                $totals = $this->calculateBookingTotal(
                    $package,
                    $data['number_of_adults'] ?? 0,
                    $data['number_of_children'] ?? 0
                );

                // Check available slots
                $this->checkAvailableSlots($package, $totals['total_travelers'], $data['travel_date']);

                // Prepare booking data
                $bookingData = $this->prepareBookingData($data, $userId, $package, $totals);

                // Create booking
                $booking = $this->bookingRepository->create($bookingData);

                // Create travelers if provided
                if (isset($data['travelers']) && is_array($data['travelers'])) {
                    $this->createMultipleTravelers($booking, $data['travelers']);
                }

                // Log successful booking
                Log::info('Booking created successfully', [
                    'booking_id' => $booking->id,
                    'booking_reference' => $booking->booking_reference,
                    'package_id' => $package->id,
                    'travelers' => $totals['total_travelers'],
                ]);

                return $booking->fresh(['travelPackage', 'travelers', 'user']);

            } catch (\Exception $e) {
                Log::error('Booking creation failed', [
                    'error' => $e->getMessage(),
                    'data' => $data,
                ]);
                throw $e;
            }
        });
    }

    /**
     * Validate package availability
     */
    protected function validatePackageAvailability(TravelPackage $package, array $data): void
    {
        if (!$package->is_active) {
            throw new \Exception('This package is not available for booking');
        }

        // Validate travel date
        if (isset($data['travel_date'])) {
            $travelDate = \Carbon\Carbon::parse($data['travel_date']);
            
            if ($travelDate->isPast()) {
                throw new \Exception('Travel date must be in the future');
            }

            if ($package->start_date && $travelDate < $package->start_date) {
                throw new \Exception('Travel date is before package start date');
            }

            if ($package->end_date && $travelDate > $package->end_date) {
                throw new \Exception('Travel date is after package end date');
            }
        }
    }

    /**
     * Calculate booking total with group pricing
     */
    protected function calculateBookingTotal(
        TravelPackage $package,
        int $adults,
        int $children
    ): array {
        $adultPrice = $package->price;
        $childPrice = $package->child_price ?? ($package->price * 0.7); // 30% discount if not specified

        $adultTotal = $adults * $adultPrice;
        $childTotal = $children * $childPrice;
        $totalAmount = $adultTotal + $childTotal;
        $totalTravelers = $adults + $children;

        // Validate min/max travelers
        if ($totalTravelers < $package->min_travelers) {
            throw new \Exception("Minimum {$package->min_travelers} travelers required for this package");
        }

        if ($totalTravelers > $package->max_travelers) {
            throw new \Exception("Maximum {$package->max_travelers} travelers allowed for this package");
        }

        // Apply group discounts if applicable
        $discount = $this->calculateGroupDiscount($totalTravelers, $totalAmount);

        return [
            'total_travelers' => $totalTravelers,
            'number_of_adults' => $adults,
            'number_of_children' => $children,
            'adult_price' => $adultPrice,
            'child_price' => $childPrice,
            'adult_total' => $adultTotal,
            'child_total' => $childTotal,
            'subtotal' => $totalAmount,
            'discount_amount' => $discount,
            'total_amount' => $totalAmount - $discount,
        ];
    }

    /**
     * Calculate group discount
     */
    protected function calculateGroupDiscount(int $travelers, float $subtotal): float
    {
        $discount = 0;

        // Group discount tiers
        if ($travelers >= 20) {
            $discount = $subtotal * 0.15; // 15% discount for 20+ travelers
        } elseif ($travelers >= 10) {
            $discount = $subtotal * 0.10; // 10% discount for 10-19 travelers
        } elseif ($travelers >= 5) {
            $discount = $subtotal * 0.05; // 5% discount for 5-9 travelers
        }

        return round($discount, 2);
    }

    /**
     * Check available slots for package
     */
    protected function checkAvailableSlots(TravelPackage $package, int $requestedTravelers, string $travelDate): void
    {
        if (!$package->available_slots) {
            return; // Unlimited slots
        }

        // Get existing bookings for the same date
        $bookedSlots = Booking::where('travel_package_id', $package->id)
            ->where('travel_date', $travelDate)
            ->whereNotIn('booking_status', ['cancelled'])
            ->sum('number_of_travelers');

        $availableSlots = $package->available_slots - $bookedSlots;

        if ($requestedTravelers > $availableSlots) {
            throw new \Exception("Only {$availableSlots} slots available. You requested {$requestedTravelers} travelers.");
        }
    }

    /**
     * Prepare booking data
     */
    protected function prepareBookingData(array $data, ?string $userId, TravelPackage $package, array $totals): array
{
    $bookingData = [
        'user_id' => $userId,
        'travel_package_id' => $package->id,
        'number_of_travelers' => $totals['total_travelers'],
        'number_of_adults' => $totals['number_of_adults'],
        'number_of_children' => $totals['number_of_children'],
        'travel_date' => $data['travel_date'],
        'total_amount' => $totals['total_amount'],
        'amount_paid' => 0,
        'amount_due' => $totals['total_amount'],
        'payment_status' => 'pending',
        'booking_status' => 'pending',
        'special_requests' => $data['special_requests'] ?? null,
    ];

    // Add guest information ONLY if not logged in
    if (!$userId) {
        $bookingData['guest_first_name'] = $data['guest_first_name'] ?? null;
        $bookingData['guest_last_name'] = $data['guest_last_name'] ?? null;
        $bookingData['guest_email'] = $data['guest_email'] ?? null;
        $bookingData['guest_phone'] = $data['guest_phone'] ?? null;
    }

    return $bookingData;
}

    /**
     * Create multiple travelers atomically
     */
    protected function createMultipleTravelers(Booking $booking, array $travelersData): void
    {
        $expectedCount = $booking->number_of_travelers;
        $providedCount = count($travelersData);

        if ($providedCount > $expectedCount) {
            throw new \Exception("Too many travelers provided. Expected {$expectedCount}, got {$providedCount}");
        }

        foreach ($travelersData as $index => $travelerData) {
            try {
                // Validate required fields
                if (empty($travelerData['first_name']) || empty($travelerData['last_name'])) {
                    throw new \Exception("Traveler #{($index + 1)}: First name and last name are required");
                }

                if (empty($travelerData['traveler_type'])) {
                    throw new \Exception("Traveler #{($index + 1)}: Traveler type is required");
                }

                // Create traveler
                $booking->travelers()->create($travelerData);

            } catch (\Exception $e) {
                throw new \Exception("Error creating traveler #{($index + 1)}: " . $e->getMessage());
            }
        }

        Log::info('Travelers created successfully', [
            'booking_id' => $booking->id,
            'travelers_count' => count($travelersData),
        ]);
    }

    /**
     * Get booking by ID
     */
    public function getBookingById(string $id): ?Booking
    {
        return $this->bookingRepository->find($id);
    }

    /**
     * Get booking by reference
     */
    public function getBookingByReference(string $reference): ?Booking
    {
        return $this->bookingRepository->findByReference($reference);
    }

    /**
     * Get user bookings
     */
    public function getUserBookings(string $userId, int $perPage = 15)
    {
        return $this->bookingRepository->getUserBookings($userId, $perPage);
    }

    /**
     * Get all bookings (admin)
     */
    public function getAllBookings(int $perPage = 15)
    {
        return $this->bookingRepository->getAllBookings($perPage);
    }

    /**
     * Confirm booking
     */
    public function confirmBooking(string $bookingId): Booking
    {
        return DB::transaction(function () use ($bookingId) {
            $booking = $this->getBookingById($bookingId);

            if (!$booking) {
                throw new \Exception('Booking not found');
            }

            if ($booking->booking_status === 'confirmed') {
                throw new \Exception('Booking is already confirmed');
            }

            if ($booking->booking_status === 'cancelled') {
                throw new \Exception('Cannot confirm a cancelled booking');
            }

            return $this->bookingRepository->update($bookingId, [
                'booking_status' => 'confirmed',
                'confirmed_at' => now(),
            ]);
        });
    }

    /**
     * Cancel booking
     */
    public function cancelBooking(
        string $bookingId,
        string $reason,
        ?string $cancelledBy = null
    ): Booking {
        return DB::transaction(function () use ($bookingId, $reason, $cancelledBy) {
            $booking = $this->getBookingById($bookingId);

            if (!$booking) {
                throw new \Exception('Booking not found');
            }

            if (!$booking->can_be_cancelled) {
                throw new \Exception('This booking cannot be cancelled');
            }

            if ($booking->booking_status === 'cancelled') {
                throw new \Exception('Booking is already cancelled');
            }

            // Check cancellation policy (3 days before travel)
            if ($booking->travel_date <= now()->addDays(3)) {
                throw new \Exception('Bookings cannot be cancelled within 3 days of travel date');
            }

            return $this->bookingRepository->update($bookingId, [
                'booking_status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
                'cancelled_by' => $cancelledBy,
            ]);
        });
    }

    /**
     * Update booking payment status
     */
    public function updatePaymentStatus(
        string $bookingId,
        float $amountPaid
    ): Booking {
        return DB::transaction(function () use ($bookingId, $amountPaid) {
            $booking = $this->getBookingById($bookingId);

            if (!$booking) {
                throw new \Exception('Booking not found');
            }

            $totalPaid = $booking->amount_paid + $amountPaid;
            $amountDue = $booking->total_amount - $totalPaid;

            // Determine payment status
            $paymentStatus = 'pending';
            if ($totalPaid >= $booking->total_amount) {
                $paymentStatus = 'paid';
            } elseif ($totalPaid > 0) {
                $paymentStatus = 'partial';
            }

            return $this->bookingRepository->update($bookingId, [
                'amount_paid' => $totalPaid,
                'amount_due' => max(0, $amountDue),
                'payment_status' => $paymentStatus,
            ]);
        });
    }

    /**
     * Add traveler to booking
     */
    public function addTraveler(string $bookingId, array $travelerData): Booking
    {
        return DB::transaction(function () use ($bookingId, $travelerData) {
            $booking = $this->getBookingById($bookingId);

            if (!$booking) {
                throw new \Exception('Booking not found');
            }

            if ($booking->travelers()->count() >= $booking->number_of_travelers) {
                throw new \Exception('Maximum number of travelers reached');
            }

            $booking->travelers()->create($travelerData);

            return $booking->fresh(['travelPackage', 'travelers', 'user']);
        });
    }

    /**
     * Get booking summary with pricing breakdown
     */
    public function getBookingSummary(string $packageId, int $adults, int $children): array
    {
        $package = TravelPackage::findOrFail($packageId);
        
        $totals = $this->calculateBookingTotal($package, $adults, $children);
        
        return [
            'package' => [
                'id' => $package->id,
                'title' => $package->title,
                'destination' => $package->destination,
            ],
            'pricing' => [
                'adult_price' => $totals['adult_price'],
                'child_price' => $totals['child_price'],
                'number_of_adults' => $adults,
                'number_of_children' => $children,
                'adult_total' => $totals['adult_total'],
                'child_total' => $totals['child_total'],
                'subtotal' => $totals['subtotal'],
                'discount' => $totals['discount_amount'],
                'discount_percentage' => $totals['discount_amount'] > 0 ? 
                    round(($totals['discount_amount'] / $totals['subtotal']) * 100, 2) : 0,
                'total' => $totals['total_amount'],
            ],
            'travelers' => [
                'total' => $totals['total_travelers'],
                'adults' => $adults,
                'children' => $children,
            ],
        ];
    }
}