<?php

namespace App\Repositories\Contracts;

use App\Models\TravelPackage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface TravelPackageRepositoryInterface
{
    public function all(): Collection;
    public function paginate(int $perPage = 15): LengthAwarePaginator;
    public function find(string $id): ?TravelPackage;
    public function findBySlug(string $slug): ?TravelPackage;
    public function create(array $data): TravelPackage;
    public function update(string $id, array $data): TravelPackage;
    public function delete(string $id): bool;
    public function getActive(int $perPage = 15): LengthAwarePaginator;
    public function getFeatured(int $limit = 6): Collection;
    public function filterPackages(array $filters, int $perPage = 15): LengthAwarePaginator;
    public function searchPackages(string $search, int $perPage = 15): LengthAwarePaginator;
}
