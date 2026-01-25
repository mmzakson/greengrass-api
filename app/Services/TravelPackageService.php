<?php

namespace App\Services;

use App\Models\TravelPackage;
use App\Repositories\Contracts\TravelPackageRepositoryInterface;
use Illuminate\Support\Facades\Storage;

class TravelPackageService
{
    public function __construct(
        protected TravelPackageRepositoryInterface $packageRepository
    ) {}

    /**
     * Get all packages (admin)
     */
    public function getAllPackages(int $perPage = 15)
    {
        return $this->packageRepository->paginate($perPage);
    }

    /**
     * Get active packages (public)
     */
    public function getActivePackages(int $perPage = 15)
    {
        return $this->packageRepository->getActive($perPage);
    }

    /**
     * Get featured packages
     */
    public function getFeaturedPackages(int $limit = 6)
    {
        return $this->packageRepository->getFeatured($limit);
    }

    /**
     * Get single package by ID
     */
    public function getPackageById(string $id): ?TravelPackage
    {
        return $this->packageRepository->find($id);
    }

    /**
     * Get package by slug
     */
    public function getPackageBySlug(string $slug): ?TravelPackage
    {
        return $this->packageRepository->findBySlug($slug);
    }

    /**
     * Create new package
     */
    public function createPackage(array $data, string $adminId): TravelPackage
    {
        $data['created_by'] = $adminId;
        
        // Handle image uploads
        if (isset($data['featured_image'])) {
            $data['featured_image'] = $this->uploadImage($data['featured_image'], 'tours');
        }

        if (isset($data['images']) && is_array($data['images'])) {
            $data['images'] = $this->uploadMultipleImages($data['images'], 'tours');
        }

        return $this->packageRepository->create($data);
    }

    /**
     * Update package
     */
    public function updatePackage(string $id, array $data): TravelPackage
    {
        $package = $this->getPackageById($id);

        if (!$package) {
            throw new \Exception('Package not found');
        }

        // Handle featured image update
        if (isset($data['featured_image'])) {
            // Delete old image
            if ($package->featured_image) {
                Storage::disk('tours')->delete($package->featured_image);
            }
            $data['featured_image'] = $this->uploadImage($data['featured_image'], 'tours');
        }

        // Handle multiple images update
        if (isset($data['images']) && is_array($data['images'])) {
            // Delete old images
            if ($package->images) {
                foreach ($package->images as $oldImage) {
                    Storage::disk('tours')->delete($oldImage);
                }
            }
            $data['images'] = $this->uploadMultipleImages($data['images'], 'tours');
        }

        return $this->packageRepository->update($id, $data);
    }

    /**
     * Delete package
     */
    public function deletePackage(string $id): bool
    {
        $package = $this->getPackageById($id);

        if (!$package) {
            throw new \Exception('Package not found');
        }

        // Check if package has bookings
        if ($package->bookings()->count() > 0) {
            throw new \Exception('Cannot delete package with existing bookings. Consider deactivating instead.');
        }

        // Delete images
        if ($package->featured_image) {
            Storage::disk('tours')->delete($package->featured_image);
        }

        if ($package->images) {
            foreach ($package->images as $image) {
                Storage::disk('tours')->delete($image);
            }
        }

        return $this->packageRepository->delete($id);
    }

    /**
     * Toggle package status
     */
    public function toggleStatus(string $id): TravelPackage
    {
        $package = $this->getPackageById($id);

        if (!$package) {
            throw new \Exception('Package not found');
        }

        return $this->packageRepository->update($id, [
            'is_active' => !$package->is_active
        ]);
    }

    /**
     * Toggle featured status
     */
    public function toggleFeatured(string $id): TravelPackage
    {
        $package = $this->getPackageById($id);

        if (!$package) {
            throw new \Exception('Package not found');
        }

        return $this->packageRepository->update($id, [
            'is_featured' => !$package->is_featured
        ]);
    }

    /**
     * Filter packages
     */
    public function filterPackages(array $filters, int $perPage = 15)
    {
        return $this->packageRepository->filterPackages($filters, $perPage);
    }

    /**
     * Search packages
     */
    public function searchPackages(string $search, int $perPage = 15)
    {
        return $this->packageRepository->searchPackages($search, $perPage);
    }

    /**
     * Upload single image
     */
    protected function uploadImage($image, string $disk): string
    {
        if (is_string($image)) {
            return $image; // Already a path
        }

        $path = $image->store('/', $disk);
        return $path;
    }

    /**
     * Upload multiple images
     */
    protected function uploadMultipleImages(array $images, string $disk): array
    {
        $paths = [];
        foreach ($images as $image) {
            if (!is_string($image)) {
                $paths[] = $image->store('/', $disk);
            } else {
                $paths[] = $image;
            }
        }
        return $paths;
    }
}