<?php

namespace App\Http\Requests\Package;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('sanctum')->check() && 
               auth('sanctum')->user() instanceof \App\Models\Admin;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string'],
            'highlights' => ['nullable', 'array'],
            'highlights.*' => ['string'],
            'inclusions' => ['nullable', 'array'],
            'inclusions.*' => ['string'],
            'exclusions' => ['nullable', 'array'],
            'exclusions.*' => ['string'],
            'destination' => ['sometimes', 'required', 'string', 'max:255'],
            'country' => ['sometimes', 'required', 'string', 'max:100'],
            'duration_days' => ['sometimes', 'required', 'integer', 'min:1'],
            'duration_nights' => ['sometimes', 'required', 'integer', 'min:0'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'child_price' => ['nullable', 'numeric', 'min:0'],
            'max_travelers' => ['sometimes', 'required', 'integer', 'min:1'],
            'min_travelers' => ['sometimes', 'required', 'integer', 'min:1'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'type' => ['sometimes', 'required', Rule::in(['group', 'private', 'custom'])],
            'category' => ['sometimes', 'required', Rule::in(['adventure', 'luxury', 'budget', 'cultural', 'religious', 'beach', 'safari'])],
            'hotel_class' => ['sometimes', 'required', Rule::in(['5_star', '4_star', '3_star', '2_star', 'budget'])],
            'difficulty_level' => ['sometimes', 'required', Rule::in(['easy', 'moderate', 'challenging'])],
            'itinerary' => ['nullable', 'array'],
            'itinerary.*.day' => ['required', 'integer'],
            'itinerary.*.title' => ['required', 'string'],
            'itinerary.*.description' => ['required', 'string'],
            'featured_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'available_slots' => ['nullable', 'integer', 'min:0'],
        ];
    }
}