<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\TravelPackage;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Illuminate\Support\Facades\DB;

class BookingServiceOld
{
    public function __construct(
        protected BookingRepositoryInterface $bookingRepository
    ) {}

    /**
     * Create a new booking
     */
    public function createBooking(array $data, ?string $userId = null): Booking
    {
        return DB::transaction(function () use ($data, $userId) {
            // Get package
            $package = TravelPackage::findOrFail($data['travel_package_id']);

            // Validate package is active and available
            if (!$package->is_active) {
                throw new \Exception('This package is not available for booking');
            }

            // Validate travel date
            if ($package->end_date && $data['travel_date'] > $package->end_date) {
                throw new \Exception('Selected travel date is not available');
            }

            // Calculate totals
            $totals = $this->calculateBookingTotal(
                $package,
                $data['number_of_adults'] ?? 0,
                $data['number_of_children'] ?? 0
            );

            // Prepare booking data
            $bookingData = [
                'user_id' => $userId,
                'travel_package_id' => $package->id,
                'number_of_travelers' => $totals['total_travelers'],
                'number_of_adults' => $data['number_of_adults'] ?? 0,
                'number_of_children' => $data['number_of_children'] ?? 0,
                'travel_date' => $data['travel_date'],
                'total_amount' => $totals['total_amount'],
                'amount_paid' => 0,
                'amount_due' => $totals['total_amount'],
                'payment_status' => 'pending',
                'booking_status' => 'pending',
                'special_requests' => $data['special_requests'] ?? null,
            ];

            // Add guest information if not logged in
            if (!$userId) {
                $bookingData['guest_first_name'] = $data['guest_first_name'];
                $bookingData['guest_last_name'] = $data['guest_last_name'];
                $bookingData['guest_email'] = $data['guest_email'];
                $bookingData['guest_phone'] = $data['guest_phone'] ?? null;
            }

            // Create booking
            $booking = $this->bookingRepository->create($bookingData);

            // Create travelers if provided
            if (isset($data['travelers']) && is_array($data['travelers'])) {
                foreach ($data['travelers'] as $travelerData) {
                    $booking->travelers()->create($travelerData);
                }
            }

            return $booking->fresh(['travelPackage', 'travelers', 'user']);
        });
    }

    /**
     * Calculate booking total
     */
    protected function calculateBookingTotal(
        TravelPackage $package,
        int $adults,
        int $children
    ): array {
        $adultPrice = $package->price;
        $childPrice = $package->child_price ?? $package->price;

        $adultTotal = $adults * $adultPrice;
        $childTotal = $children * $childPrice;
        $totalAmount = $adultTotal + $childTotal;
        $totalTravelers = $adults + $children;

        // Validate min/max travelers
        if ($totalTravelers < $package->min_travelers) {
            throw new \Exception("Minimum {$package->min_travelers} travelers required");
        }

        if ($totalTravelers > $package->max_travelers) {
            throw new \Exception("Maximum {$package->max_travelers} travelers allowed");
        }

        return [
            'total_travelers' => $totalTravelers,
            'adult_total' => $adultTotal,
            'child_total' => $childTotal,
            'total_amount' => $totalAmount,
        ];
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
        $booking = $this->getBookingById($bookingId);

        if (!$booking) {
            throw new \Exception('Booking not found');
        }

        if ($booking->travelers()->count() >= $booking->number_of_travelers) {
            throw new \Exception('Maximum number of travelers reached');
        }

        $booking->travelers()->create($travelerData);

        return $booking->fresh(['travelPackage', 'travelers', 'user']);
    }
}