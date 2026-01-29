<?php

namespace App\Repositories\Eloquent;

use App\Models\Booking;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class BookingRepository implements BookingRepositoryInterface
{
    public function __construct(
        protected Booking $model
    ) {}

    public function find(string $id): ?Booking
    {
        return $this->model
            ->with(['travelPackage', 'travelers', 'user'])
            ->find($id);
    }

    public function findByReference(string $reference): ?Booking
    {
        return $this->model
            ->with(['travelPackage', 'travelers', 'user'])
            ->where('booking_reference', $reference)
            ->first();
    }

    public function create(array $data): Booking
    {
        return $this->model->create($data);
    }

    public function update(string $id, array $data): Booking
    {
        $booking = $this->find($id);
        $booking->update($data);
        return $booking->fresh(['travelPackage', 'travelers', 'user']);
    }

    public function delete(string $id): bool
    {
        $booking = $this->find($id);
        return $booking->delete();
    }

    public function getUserBookings(string $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with(['travelPackage', 'travelers'])
            ->forUser($userId)
            ->latest()
            ->paginate($perPage);
    }

    public function getGuestBookingByEmail(string $email): Collection
    {
        return $this->model
            ->with(['travelPackage', 'travelers'])
            ->where('guest_email', $email)
            ->latest()
            ->get();
    }

    public function getAllBookings(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with(['travelPackage', 'user', 'travelers'])
            ->latest()
            ->paginate($perPage);
    }

    public function getUpcomingBookings(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with(['travelPackage', 'user'])
            ->upcoming()
            ->latest('travel_date')
            ->paginate($perPage);
    }

    public function getPendingBookings(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with(['travelPackage', 'user'])
            ->pending()
            ->latest()
            ->paginate($perPage);
    }
}