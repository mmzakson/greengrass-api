<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateAdminRequest;
use App\Http\Requests\Admin\UpdateAdminRequest;
use App\Http\Resources\Admin\AdminResource;
use App\Models\Admin;
use App\Services\AdminAuthService;
use Illuminate\Http\JsonResponse;

class AdminManagementController extends Controller
{
    public function __construct(
        protected AdminAuthService $adminAuthService
    ) {}

    /**
     * Get all admins
     */
    public function index(): JsonResponse
    {
        $admins = Admin::latest()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => AdminResource::collection($admins->items()),
            'pagination' => [
                'total' => $admins->total(),
                'per_page' => $admins->perPage(),
                'current_page' => $admins->currentPage(),
                'last_page' => $admins->lastPage(),
            ],
        ]);
    }

    /**
     * Create new admin
     */
    public function store(CreateAdminRequest $request): JsonResponse
    {
        try {
            $admin = $this->adminAuthService->createAdmin($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Admin created successfully',
                'data' => new AdminResource($admin),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create admin',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single admin
     */
    public function show(Admin $admin): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new AdminResource($admin),
        ]);
    }

    /**
     * Update admin
     */
    public function update(UpdateAdminRequest $request, Admin $admin): JsonResponse
    {
        try {
            $updated = $this->adminAuthService->updateAdmin($admin, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Admin updated successfully',
                'data' => new AdminResource($updated),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update admin',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Deactivate admin
     */
    public function deactivate(Admin $admin): JsonResponse
    {
        try {
            $this->adminAuthService->deactivateAdmin($admin);

            return response()->json([
                'success' => true,
                'message' => 'Admin account deactivated',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to deactivate admin',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Activate admin
     */
    public function activate(Admin $admin): JsonResponse
    {
        try {
            $this->adminAuthService->activateAdmin($admin);

            return response()->json([
                'success' => true,
                'message' => 'Admin account activated',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to activate admin',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}