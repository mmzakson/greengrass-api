<?php

namespace App\Http\Requests\Package;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('sanctum')->check() && 
               auth('sanctum')->user() instanceof \App\Models\Admin;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'highlights' => ['nullable', 'array'],
            'highlights.*' => ['string'],
            'inclusions' => ['nullable', 'array'],
            'inclusions.*' => ['string'],
            'exclusions' => ['nullable', 'array'],
            'exclusions.*' => ['string'],
            'destination' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:100'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'duration_nights' => ['required', 'integer', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'child_price' => ['nullable', 'numeric', 'min:0'],
            'max_travelers' => ['required', 'integer', 'min:1'],
            'min_travelers' => ['required', 'integer', 'min:1'],
            'start_date' => ['nullable', 'date', 'after_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'type' => ['required', Rule::in(['group', 'private', 'custom'])],
            'category' => ['required', Rule::in(['adventure', 'luxury', 'budget', 'cultural', 'religious', 'beach', 'safari'])],
            'hotel_class' => ['required', Rule::in(['5_star', '4_star', '3_star', '2_star', 'budget'])],
            'difficulty_level' => ['required', Rule::in(['easy', 'moderate', 'challenging'])],
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

    public function messages(): array
    {
        return [
            'title.required' => 'Package title is required',
            'description.required' => 'Package description is required',
            'destination.required' => 'Destination is required',
            'country.required' => 'Country is required',
            'duration_days.required' => 'Duration in days is required',
            'duration_nights.required' => 'Duration in nights is required',
            'price.required' => 'Price is required',
            'price.min' => 'Price must be greater than or equal to 0',
            'max_travelers.required' => 'Maximum travelers is required',
            'min_travelers.required' => 'Minimum travelers is required',
            'type.required' => 'Package type is required',
            'type.in' => 'Invalid package type',
            'category.required' => 'Category is required',
            'category.in' => 'Invalid category',
            'hotel_class.required' => 'Hotel class is required',
            'hotel_class.in' => 'Invalid hotel class',
            'difficulty_level.required' => 'Difficulty level is required',
            'featured_image.image' => 'Featured image must be an image file',
            'featured_image.max' => 'Featured image size must not exceed 2MB',
        ];
    }
}
