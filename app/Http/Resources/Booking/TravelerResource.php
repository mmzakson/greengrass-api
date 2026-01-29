<?php

namespace App\Http\Resources\Booking;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TravelerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'age' => $this->age,
            'gender' => $this->gender,
            'passport_number' => $this->passport_number,
            'passport_expiry' => $this->passport_expiry?->toDateString(),
            'nationality' => $this->nationality,
            'traveler_type' => $this->traveler_type,
            'special_needs' => $this->special_needs,
        ];
    }
}
