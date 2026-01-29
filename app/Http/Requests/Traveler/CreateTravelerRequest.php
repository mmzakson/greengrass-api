<?php

namespace App\Http\Requests\Traveler;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTravelerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'passport_number' => ['required', 'string', 'max:50'],
            'passport_expiry' => ['required', 'date', 'after:today'],
            'passport_copy' => ['nullable', 'file', 'mimes:jpeg,jpg,png,pdf', 'max:5120'], // 5MB
            'nationality' => ['required', 'string', 'max:100'],
            'traveler_type' => ['nullable', Rule::in(['adult', 'child', 'infant'])],
            'special_needs' => ['nullable', 'string', 'max:1000'],
            
            // Emergency contact
            'emergency_contact.name' => ['nullable', 'string', 'max:255'],
            'emergency_contact.phone' => ['nullable', 'string', 'max:20'],
            'emergency_contact.relationship' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required',
            'last_name.required' => 'Last name (surname) is required',
            'date_of_birth.required' => 'Date of birth is required',
            'date_of_birth.before' => 'Date of birth must be in the past',
            'passport_number.required' => 'Passport number is required',
            'passport_expiry.required' => 'Passport expiry date is required',
            'passport_expiry.after' => 'Passport must be valid (not expired)',
            'passport_copy.mimes' => 'Passport must be a JPEG, PNG, or PDF file',
            'passport_copy.max' => 'Passport file size must not exceed 5MB',
            'nationality.required' => 'Nationality is required',
        ];
    }

    /**
     * Prepare data for validation
     */
    protected function prepareForValidation(): void
    {
        // Auto-determine traveler type if not provided
        if (!$this->has('traveler_type') && $this->has('date_of_birth')) {
            $dob = new \DateTime($this->date_of_birth);
            $age = $dob->diff(new \DateTime())->y;

            $type = 'adult';
            if ($age < 2) {
                $type = 'infant';
            } elseif ($age < 18) {
                $type = 'child';
            }

            $this->merge(['traveler_type' => $type]);
        }
    }
}
