<?php

namespace App\Services;


use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class AdminAuthService
{
    /**
     * Authenticate admin and generate token
     */
    public function login(array $credentials, bool $remember = false): array|false
    {
        $admin = Admin::where('email', $credentials['email'])->first();

        if (!$admin || !Hash::check($credentials['password'], $admin->password)) {
            return false;
        }

        if (!$admin->is_active) {
            throw new \Exception('Your admin account has been deactivated. Please contact super admin.');
        }

        // Revoke old tokens if not remembering
        if (!$remember) {
            $admin->tokens()->delete();
        }

        // Create new token with admin abilities
        $abilities = $this->getAbilitiesForRole($admin->role);
        $token = $admin->createToken('admin-auth-token', $abilities)->plainTextToken;

        return [
            'admin' => $admin,
            'token' => $token,
        ];
    }

    /**
     * Logout admin (revoke current token)
     */
    public function logout(Admin $admin): bool
    {
        $admin->currentAccessToken()->delete();
        return true;
    }

    /**
     * Logout from all devices (revoke all tokens)
     */
    public function logoutAllDevices(Admin $admin): bool
    {
        $admin->tokens()->delete();
        return true;
    }

    /**
     * Create a new admin (only by super admin)
     */
    public function createAdmin(array $data): Admin
    {
        return Admin::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'is_active' => true,
        ]);
    }

    /**
     * Update admin details
     */
    public function updateAdmin(Admin $admin, array $data): Admin
    {
        $admin->update($data);
        return $admin->fresh();
    }

    /**
     * Change admin password
     */
    public function changePassword(Admin $admin, string $currentPassword, string $newPassword): bool
    {
        if (!Hash::check($currentPassword, $admin->password)) {
            throw new \Exception('Current password is incorrect');
        }

        $admin->update([
            'password' => Hash::make($newPassword),
        ]);

        // Revoke all tokens to force re-login
        $admin->tokens()->delete();

        return true;
    }

    /**
     * Deactivate admin account
     */
    public function deactivateAdmin(Admin $admin): bool
    {
        $admin->update(['is_active' => false]);
        $admin->tokens()->delete(); // Logout from all devices
        return true;
    }

    /**
     * Activate admin account
     */
    public function activateAdmin(Admin $admin): bool
    {
        $admin->update(['is_active' => true]);
        return true;
    }

    /**
     * Get abilities based on admin role
     */
    private function getAbilitiesForRole(string $role): array
    {
        return match($role) {
            'super_admin' => ['*'], // All permissions
            'admin' => [
                'manage-packages',
                'manage-bookings',
                'view-users',
                'manage-reviews',
            ],
            'manager' => [
                'view-packages',
                'view-bookings',
                'view-users',
            ],
            default => [],
        };
    }
}