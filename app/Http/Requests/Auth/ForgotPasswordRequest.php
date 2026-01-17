<?php

// ============================================================================
// FILE 5: Forgot Password Request Validation
// Path: app/Http/Requests/Auth/ForgotPasswordRequest.php
// ============================================================================

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'exists:users,email'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Please enter your email address',
            'email.email' => 'Please enter a valid email address',
            'email.exists' => 'We could not find an account with this email address',
        ];
    }
}
