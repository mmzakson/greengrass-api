<?php

namespace App\Http\Resources\Booking;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_reference' => $this->booking_reference,
            'customer_name' => $this->customer_name,
            'customer_email' => $this->customer_email,
            'is_guest_booking' => $this->is_guest_booking,
            'package' => [
                'id' => $this->travelPackage->id,
                'title' => $this->travelPackage->title,
                'destination' => $this->travelPackage->destination,
            ],
            'travel_date' => $this->travel_date->toDateString(),
            'number_of_travelers' => $this->number_of_travelers,
            'total_amount' => (float) $this->total_amount,
            'amount_paid' => (float) $this->amount_paid,
            'amount_due' => (float) $this->amount_due,
            'payment_status' => $this->payment_status,
            'booking_status' => $this->booking_status,
            'is_confirmed' => $this->is_confirmed,
            'is_cancelled' => $this->is_cancelled,
            'can_be_cancelled' => $this->can_be_cancelled,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
