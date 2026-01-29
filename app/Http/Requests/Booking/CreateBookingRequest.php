<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class CreateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Allow both guest and authenticated users
    }

    public function rules(): array
    {
        $rules = [
            'travel_package_id' => ['required', 'uuid', 'exists:travel_packages,id'],
            'travel_date' => ['required', 'date', 'after:today'],
            'number_of_adults' => ['required', 'integer', 'min:1'],
            'number_of_children' => ['nullable', 'integer', 'min:0'],
            'special_requests' => ['nullable', 'string', 'max:1000'],
            
            // Travelers (optional at booking, can be added later)
            'travelers' => ['nullable', 'array'],
            'travelers.*.first_name' => ['required', 'string', 'max:255'],
            'travelers.*.last_name' => ['required', 'string', 'max:255'],
            'travelers.*.email' => ['nullable', 'email'],
            'travelers.*.phone' => ['nullable', 'string', 'max:20'],
            'travelers.*.date_of_birth' => ['nullable', 'date', 'before:today'],
            'travelers.*.gender' => ['nullable', 'in:male,female,other'],
            'travelers.*.traveler_type' => ['required', 'in:adult,child,infant'],
            'travelers.*.passport_number' => ['nullable', 'string', 'max:50'],
            'travelers.*.nationality' => ['nullable', 'string', 'max:100'],
        ];

        // Guest booking fields (required if not authenticated)
        if (!auth()->check()) {
            $rules['guest_first_name'] = ['required', 'string', 'max:255'];
            $rules['guest_last_name'] = ['required', 'string', 'max:255'];
            $rules['guest_email'] = ['required', 'email', 'max:255'];
            $rules['guest_phone'] = ['nullable', 'string', 'max:20'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'travel_package_id.required' => 'Please select a travel package',
            'travel_package_id.exists' => 'Selected package does not exist',
            'travel_date.required' => 'Please select a travel date',
            'travel_date.after' => 'Travel date must be in the future',
            'number_of_adults.required' => 'Please specify number of adults',
            'number_of_adults.min' => 'At least one adult is required',
            'guest_first_name.required' => 'First name is required',
            'guest_last_name.required' => 'Last name is required',
            'guest_email.required' => 'Email is required for booking confirmation',
        ];
    }
}
