<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class CreateAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only super admins can create other admins
        return auth('sanctum')->check() && 
               auth('sanctum')->user() instanceof \App\Models\Admin &&
               auth('sanctum')->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:admins,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
            ],
            'role' => ['required', Rule::in(['super_admin', 'admin', 'manager'])],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please enter admin name',
            'email.required' => 'Please enter email address',
            'email.unique' => 'This email is already registered',
            'password.required' => 'Please enter a password',
            'password.confirmed' => 'Password confirmation does not match',
            'role.required' => 'Please select a role',
            'role.in' => 'Invalid role selected',
        ];
    }
}
