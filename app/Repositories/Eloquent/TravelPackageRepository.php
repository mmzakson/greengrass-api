<?php

namespace App\Repositories\Eloquent;

use App\Models\TravelPackage;
use App\Repositories\Contracts\TravelPackageRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class TravelPackageRepository implements TravelPackageRepositoryInterface
{
    public function __construct(
        protected TravelPackage $model
    ) {}

    public function all(): Collection
    {
        return $this->model->with('creator'/*, 'reviews'*/)->latest()->get();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with('creator'/*, 'reviews'*/)
            ->withCount('bookings')
            ->latest()
            ->paginate($perPage);
    }

    public function find(string $id): ?TravelPackage
    {
        return $this->model
            ->with('creator'/*, 'reviews'*/, 'availability')
            ->withCount('bookings')
            ->find($id);
    }

    public function findBySlug(string $slug): ?TravelPackage
    {
        return $this->model
            ->with('creator'/*, 'reviews'*/, 'availability')
            ->withCount('bookings')
            ->where('slug', $slug)
            ->first();
    }

    public function create(array $data): TravelPackage
    {
        return $this->model->create($data);
    }

    public function update(string $id, array $data): TravelPackage
    {
        $package = $this->find($id);
        $package->update($data);
        return $package->fresh();
    }

    public function delete(string $id): bool
    {
        $package = $this->find($id);
        return $package->delete();
    }

    public function getActive(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->active()
            ->available()
            ->with('creator')
            ->withCount('bookings')
            ->latest()
            ->paginate($perPage);
    }

    public function getFeatured(int $limit = 6): Collection
    {
        return $this->model
            ->active()
            ->featured()
            ->available()
            ->with('creator')
            ->limit($limit)
            ->get();
    }

    public function filterPackages(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query()->active()->available();

        if (!empty($filters['hotel_class'])) {
            $query->byHotelClass($filters['hotel_class']);
        }

        if (!empty($filters['category'])) {
            $query->byCategory($filters['category']);
        }

        if (!empty($filters['type'])) {
            $query->byType($filters['type']);
        }

        if (!empty($filters['destination'])) {
            $query->byDestination($filters['destination']);
        }

        if (isset($filters['min_price']) || isset($filters['max_price'])) {
            $query->byPriceRange(
                $filters['min_price'] ?? null,
                $filters['max_price'] ?? null
            );
        }

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->with('creator')->withCount('bookings')->paginate($perPage);
    }

    public function searchPackages(string $search, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->active()
            ->available()
            ->search($search)
            ->with('creator')
            ->withCount('bookings')
            ->paginate($perPage);
    }
}