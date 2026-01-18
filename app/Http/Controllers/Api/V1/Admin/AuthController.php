<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Http\Requests\Admin\ChangePasswordRequest;
use App\Http\Resources\Admin\AdminResource;
use App\Services\AdminAuthService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(
        protected AdminAuthService $adminAuthService
    ) {}

    /**
     * Admin login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->adminAuthService->login(
                $request->only('email', 'password'),
                $request->boolean('remember')
            );

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials',
                ], 401);
            }

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'admin' => new AdminResource($result['admin']),
                    'token' => $result['token'],
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Get authenticated admin
     */
    public function me(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new AdminResource(auth()->user()),
        ], 200);
    }

    /**
     * Logout admin (current device)
     */
    public function logout(): JsonResponse
    {
        try {
            $this->adminAuthService->logout(auth()->user());

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Logout from all devices
     */
    public function logoutAllDevices(): JsonResponse
    {
        try {
            $this->adminAuthService->logoutAllDevices(auth()->user());

            return response()->json([
                'success' => true,
                'message' => 'Logged out from all devices',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Change admin password
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        try {
            $this->adminAuthService->changePassword(
                auth()->user(),
                $request->current_password,
                $request->password
            );

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully. Please login again.',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
