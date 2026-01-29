<?php

namespace App\Http\Requests\Traveler;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTravelerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['sometimes', 'required', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'passport_number' => ['sometimes', 'required', 'string', 'max:50'],
            'passport_expiry' => ['sometimes', 'required', 'date', 'after:today'],
            'passport_copy' => ['nullable', 'file', 'mimes:jpeg,jpg,png,pdf', 'max:5120'],
            'nationality' => ['sometimes', 'required', 'string', 'max:100'],
            'traveler_type' => ['sometimes', Rule::in(['adult', 'child', 'infant'])],
            'special_needs' => ['nullable', 'string', 'max:1000'],
            
            // Emergency contact
            'emergency_contact.name' => ['nullable', 'string', 'max:255'],
            'emergency_contact.phone' => ['nullable', 'string', 'max:20'],
            'emergency_contact.relationship' => ['nullable', 'string', 'max:100'],
        ];
    }
}
