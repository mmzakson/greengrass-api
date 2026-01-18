<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('sanctum')->check() && 
               auth('sanctum')->user() instanceof \App\Models\Admin;
    }

    public function rules(): array
    {
        $adminId = $this->route('admin'); // Get admin ID from route

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('admins', 'email')->ignore($adminId)
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['sometimes', 'required', Rule::in(['super_admin', 'admin', 'manager'])],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
