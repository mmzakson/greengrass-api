<?php

namespace App\Http\Resources\Package;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'highlights' => $this->highlights,
            'inclusions' => $this->inclusions,
            'exclusions' => $this->exclusions,
            'destination' => $this->destination,
            'country' => $this->country,
            'duration' => $this->duration,
            'duration_days' => $this->duration_days,
            'duration_nights' => $this->duration_nights,
            'price' => (float) $this->price,
            'child_price' => $this->child_price ? (float) $this->child_price : null,
            'max_travelers' => $this->max_travelers,
            'min_travelers' => $this->min_travelers,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'type' => $this->type,
            'category' => $this->category,
            'hotel_class' => $this->hotel_class,
            'difficulty_level' => $this->difficulty_level,
            'itinerary' => $this->itinerary,
            'featured_image' => $this->featured_image ? url("storage/tours/{$this->featured_image}") : null,
            'images' => $this->images ? array_map(fn($img) => url("storage/tours/{$img}"), $this->images) : [],
            'is_featured' => $this->is_featured,
            'is_active' => $this->is_active,
            'available_slots' => $this->available_slots,
            'average_rating' => round($this->average_rating, 1),
            'total_reviews' => $this->total_reviews,
            'total_bookings' => $this->bookings_count ?? 0,
            'created_by' => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ] : null,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
