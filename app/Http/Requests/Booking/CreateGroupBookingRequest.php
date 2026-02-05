<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class CreateGroupBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'travel_package_id' => ['required', 'uuid', 'exists:travel_packages,id'],
            'travel_date' => ['required', 'date', 'after:today'],
            'number_of_adults' => ['required', 'integer', 'min:1', 'max:50'],
            'number_of_children' => ['nullable', 'integer', 'min:0', 'max:30'],
            'special_requests' => ['nullable', 'string', 'max:2000'],
            
            // Guest fields
            'guest_first_name' => ['nullable', 'string', 'max:255'],
            'guest_last_name' => ['nullable', 'string', 'max:255'],
            'guest_email' => ['nullable', 'email', 'max:255'],
            'guest_phone' => ['nullable', 'string', 'max:20'],
            
            // Group travelers (all travelers can be provided at once)
            'travelers' => ['nullable', 'array', 'max:50'],
            'travelers.*.first_name' => ['required', 'string', 'max:255'],
            'travelers.*.middle_name' => ['nullable', 'string', 'max:255'],
            'travelers.*.last_name' => ['required', 'string', 'max:255'],
            'travelers.*.email' => ['nullable', 'email'],
            'travelers.*.phone' => ['nullable', 'string', 'max:20'],
            'travelers.*.date_of_birth' => ['required', 'date', 'before:today'],
            'travelers.*.gender' => ['nullable', 'in:male,female,other'],
            'travelers.*.traveler_type' => ['required', 'in:adult,child,infant'],
            'travelers.*.passport_number' => ['nullable', 'string', 'max:50'],
            'travelers.*.passport_expiry' => ['nullable', 'date', 'after:today'],
            'travelers.*.nationality' => ['required', 'string', 'max:100'],
            'travelers.*.special_needs' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'travel_package_id.required' => 'Please select a travel package',
            'travel_date.required' => 'Please select a travel date',
            'travel_date.after' => 'Travel date must be in the future',
            'number_of_adults.required' => 'Please specify number of adults',
            'number_of_adults.min' => 'At least one adult is required',
            'number_of_adults.max' => 'Maximum 50 adults allowed per booking',
            'travelers.max' => 'Maximum 50 travelers can be added at once',
            'travelers.*.first_name.required' => 'First name is required for all travelers',
            'travelers.*.last_name.required' => 'Last name is required for all travelers',
            'travelers.*.date_of_birth.required' => 'Date of birth is required for all travelers',
            'travelers.*.traveler_type.required' => 'Traveler type is required',
            'travelers.*.nationality.required' => 'Nationality is required for all travelers',
        ];
    }

    /**
     * Additional validation
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate travelers count matches declared count
            if ($this->has('travelers')) {
                $declaredTotal = ($this->number_of_adults ?? 0) + ($this->number_of_children ?? 0);
                $providedCount = count($this->travelers ?? []);
                
                if ($providedCount > $declaredTotal) {
                    $validator->errors()->add(
                        'travelers',
                        "You declared {$declaredTotal} travelers but provided {$providedCount} traveler details"
                    );
                }
            }
        });
    }

    /**
     * Get validated data with additional validation logic
     */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);
        
        // If not authenticated, require guest fields
        if (!auth('sanctum')->check()) {
            $this->validate([
                'guest_first_name' => ['required', 'string', 'max:255'],
                'guest_last_name' => ['required', 'string', 'max:255'],
                'guest_email' => ['required', 'email', 'max:255'],
            ], [
                'guest_first_name.required' => 'First name is required for guest bookings',
                'guest_last_name.required' => 'Last name is required for guest bookings',
                'guest_email.required' => 'Email is required for booking confirmation',
            ]);
        }
        
        return $data;
    }
}
