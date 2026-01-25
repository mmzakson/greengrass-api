<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
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
     * List all active packages
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $packages = $this->packageService->getActivePackages($perPage);

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
     * Get featured packages
     */
    public function featured(): JsonResponse
    {
        $packages = $this->packageService->getFeaturedPackages(6);

        return response()->json([
            'success' => true,
            'data' => PackageListResource::collection($packages),
        ]);
    }

    /**
     * Show single package
     */
    public function show(string $slug): JsonResponse
    {
        $package = $this->packageService->getPackageBySlug($slug);

        if (!$package || !$package->is_active) {
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
     * Filter packages
     */
    public function filter(Request $request): JsonResponse
    {
        $filters = $request->only([
            'hotel_class',
            'category',
            'type',
            'destination',
            'min_price',
            'max_price',
            'search',
            'sort_by',
            'sort_order',
        ]);

        $perPage = $request->input('per_page', 15);
        $packages = $this->packageService->filterPackages($filters, $perPage);

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
     * Search packages
     */
    public function search(Request $request): JsonResponse
    {
        $search = $request->input('q', '');
        $perPage = $request->input('per_page', 15);

        if (empty($search)) {
            return response()->json([
                'success' => false,
                'message' => 'Search query is required',
            ], 400);
        }

        $packages = $this->packageService->searchPackages($search, $perPage);

        return response()->json([
            'success' => true,
            'data' => PackageListResource::collection($packages->items()),
            'pagination' => [
                'total' => $packages->total(),
                'per_page' => $packages->perPage(),
                'current_page' => $packages->currentPage(),
                'last_page' => $packages->lastPage(),
            ],
        ]);
    }
}
