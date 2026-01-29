<?php

namespace App\Http\Resources\Traveler;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TravelerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_id' => $this->booking_id,
            'full_name' => $this->full_name,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'age' => $this->age,
            'gender' => $this->gender,
            'passport' => [
                'number' => $this->passport_number,
                'expiry' => $this->passport_expiry?->toDateString(),
                'is_valid' => $this->is_passport_valid,
                'has_copy' => !is_null($this->passport_copy),
                'file_url' => $this->passport_url,
            ],
            'nationality' => $this->nationality,
            'traveler_type' => $this->traveler_type,
            'is_adult' => $this->is_adult,
            'special_needs' => $this->special_needs,
            'emergency_contact' => $this->emergency_contact,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
