<?php

// ============================================================================
// FILE 6: Reset Password Request Validation
// Path: app/Http/Requests/Auth/ResetPasswordRequest.php
// ============================================================================

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email', 'exists:users,email'],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'Reset token is required',
            'email.required' => 'Please enter your email address',
            'email.exists' => 'We could not find an account with this email address',
            'password.required' => 'Please enter a new password',
            'password.confirmed' => 'Password confirmation does not match',
        ];
    }
}
