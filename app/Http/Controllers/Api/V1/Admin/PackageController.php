<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Package\StorePackageRequest;
use App\Http\Requests\Package\UpdatePackageRequest;
use App\Http\Resources\Package\PackageListResource;
use App\Http\Resources\Package\PackageDetailResource;
use App\Services\TravelPackageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function __construct(
        protected TravelPackageService $packageService
    ) {}

    /**
     * List all packages (admin view - includes inactive)
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $packages = $this->packageService->getAllPackages($perPage);

        return response()->json([
            'success' => true,
            'data' => PackageListResource::collection($packages->items()),
            'pagination' => [
                'total' => $packages->total(),
                'per_page' => $packages->perPage(),
                'current_page' => $packages->currentPage(),
                'last_page' => $packages->lastPage(),
                'from' => $packages->firstItem(),
                'to' => $packages->lastItem(),
            ],
        ]);
    }

    /**
     * Create new package
     */
    public function store(StorePackageRequest $request): JsonResponse
    {
        try {
            $package = $this->packageService->createPackage(
                $request->validated(),
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Package created successfully',
                'data' => new PackageDetailResource($package),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create package',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show single package
     */
    public function show(string $id): JsonResponse
    {
        $package = $this->packageService->getPackageById($id);

        if (!$package) {
            return response()->json([
                'success' => false,
                'message' => 'Package not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new PackageDetailResource($package),
        ]);
    }

    /**
     * Update package
     */
    public function update(UpdatePackageRequest $request, string $id): JsonResponse
    {
        try {
            $package = $this->packageService->updatePackage($id, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Package updated successfully',
                'data' => new PackageDetailResource($package),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Delete package
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $this->packageService->deletePackage($id);

            return response()->json([
                'success' => true,
                'message' => 'Package deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Toggle package status
     */
    public function toggleStatus(string $id): JsonResponse
    {
        try {
            $package = $this->packageService->toggleStatus($id);

            return response()->json([
                'success' => true,
                'message' => $package->is_active ? 'Package activated' : 'Package deactivated',
                'data' => new PackageDetailResource($package),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Toggle featured status
     */
    public function toggleFeatured(string $id): JsonResponse
    {
        try {
            $package = $this->packageService->toggleFeatured($id);

            return response()->json([
                'success' => true,
                'message' => $package->is_featured ? 'Package featured' : 'Package unfeatured',
                'data' => new PackageDetailResource($package),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
