<?php
namespace App\Repositories\Contracts;

use App\Models\Booking;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface BookingRepositoryInterface
{
    public function find(string $id): ?Booking;
    public function findByReference(string $reference): ?Booking;
    public function create(array $data): Booking;
    public function update(string $id, array $data): Booking;
    public function delete(string $id): bool;
    public function getUserBookings(string $userId, int $perPage = 15): LengthAwarePaginator;
    public function getGuestBookingByEmail(string $email): Collection;
    public function getAllBookings(int $perPage = 15): LengthAwarePaginator;
    public function getUpcomingBookings(int $perPage = 15): LengthAwarePaginator;
    public function getPendingBookings(int $perPage = 15): LengthAwarePaginator;
}