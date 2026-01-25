<?php

namespace App\Models;

use App\Models\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class TravelPackage extends Model
{
    use HasFactory, UsesUuid, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'highlights',
        'inclusions',
        'exclusions',
        'destination',
        'country',
        'duration_days',
        'duration_nights',
        'price',
        'child_price',
        'max_travelers',
        'min_travelers',
        'start_date',
        'end_date',
        'type',
        'category',
        'hotel_class',
        'difficulty_level',
        'itinerary',
        'images',
        'featured_image',
        'is_featured',
        'is_active',
        'available_slots',
        'created_by',
    ];

    protected $casts = [
        'highlights' => 'array',
        'inclusions' => 'array',
        'exclusions' => 'array',
        'itinerary' => 'array',
        'images' => 'array',
        'price' => 'decimal:2',
        'child_price' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($package) {
            if (empty($package->slug)) {
                $package->slug = Str::slug($package->title);
            }
        });

        static::updating(function ($package) {
            if ($package->isDirty('title')) {
                $package->slug = Str::slug($package->title);
            }
        });
    }

    /**
     * Relationships
     */
    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'travel_package_id');
    }

    
    public function availability()
    {
        return $this->hasMany(PackageAvailability::class, 'travel_package_id');
        }
        
        /**
         * Accessors & Mutators
        */
        public function getDurationAttribute(): string
        {
            return "{$this->duration_days} Days / {$this->duration_nights} Nights";
        }
            
    // public function reviews()
    // {
    //     return $this->hasMany(Review::class, 'travel_package_id');
    // }
    
    // public function getAverageRatingAttribute(): float
    // {
    //     return (float) $this->reviews()
    //         ->where('is_approved', true)
    //         ->avg('rating') ?? 0.0;
    // }

    // public function getTotalReviewsAttribute(): int
    // {
    //     return $this->reviews()
    //         ->where('is_approved', true)
    //         ->count();
    // }

    public function getTotalBookingsAttribute(): int
    {
        return $this->bookings()->count();
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByHotelClass($query, string $hotelClass)
    {
        return $query->where('hotel_class', $hotelClass);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByDestination($query, string $destination)
    {
        return $query->where('destination', 'like', "%{$destination}%");
    }

    public function scopeByPriceRange($query, ?float $minPrice, ?float $maxPrice)
    {
        if ($minPrice) {
            $query->where('price', '>=', $minPrice);
        }
        if ($maxPrice) {
            $query->where('price', '<=', $maxPrice);
        }
        return $query;
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)
            ->where(function($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            });
    }

    public function scopeSearch($query, ?string $search)
    {
        if ($search) {
            return $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('destination', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        return $query;
    }
}
