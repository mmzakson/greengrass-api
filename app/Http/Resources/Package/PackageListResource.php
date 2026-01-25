<?php

namespace App\Http\Resources\Package;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'destination' => $this->destination,
            'country' => $this->country,
            'duration' => $this->duration,
            'duration_days' => $this->duration_days,
            'duration_nights' => $this->duration_nights,
            'price' => (float) $this->price,
            'child_price' => $this->child_price ? (float) $this->child_price : null,
            'type' => $this->type,
            'category' => $this->category,
            'hotel_class' => $this->hotel_class,
            'difficulty_level' => $this->difficulty_level,
            'featured_image' => $this->featured_image ? url("storage/tours/{$this->featured_image}") : null,
            'is_featured' => $this->is_featured,
            'is_active' => $this->is_active,
            'available_slots' => $this->available_slots,
            'average_rating' => round($this->average_rating, 1),
            'total_reviews' => $this->total_reviews,
            'total_bookings' => $this->bookings_count ?? 0,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
