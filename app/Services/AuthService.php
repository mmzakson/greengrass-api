<?php

// ============================================================================
// FILE 8: Authentication Service (Business Logic)
// Path: app/Services/AuthService.php
// ============================================================================

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

class AuthService
{
    /**
     * Register a new user
     */
    public function register(array $data): User
    {
        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'country' => $data['country'] ?? 'Nigeria',
        ]);

        // Trigger registered event (for email verification)
        event(new Registered($user));

        return $user;
    }

    /**
     * Authenticate user and generate token
     */
    public function login(array $credentials, bool $remember = false): array|false
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return false;
        }

        if (!$user->is_active) {
            throw new \Exception('Your account has been deactivated. Please contact support.');
        }

        // Revoke old tokens if not remembering
        if (!$remember) {
            $user->tokens()->delete();
        }

        // Create new token
        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Logout user (revoke current token)
     */
    public function logout(User $user): bool
    {
        // Revoke current access token
        $user->currentAccessToken()->delete();

        return true;
    }

    /**
     * Logout from all devices (revoke all tokens)
     */
    public function logoutAllDevices(User $user): bool
    {
        $user->tokens()->delete();

        return true;
    }

    /**
     * Send password reset link
     */
    public function sendResetLink(string $email): string
    {
        $status = Password::sendResetLink(['email' => $email]);

        if ($status === Password::RESET_LINK_SENT) {
            return 'Password reset link sent to your email';
        }

        throw new \Exception('Unable to send password reset link');
    }

    /**
     * Reset password
     */
    public function resetPassword(array $data): string
    {
        $status = Password::reset(
            [
                'email' => $data['email'],
                'password' => $data['password'],
                'password_confirmation' => $data['password_confirmation'],
                'token' => $data['token'],
            ],
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return 'Password has been reset successfully';
        }

        throw new \Exception('Unable to reset password. Please try again.');
    }
}