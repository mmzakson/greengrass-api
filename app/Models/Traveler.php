<?php

namespace App\Models;

use App\Models\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Traveler extends Model
{
    use HasFactory, UsesUuid, SoftDeletes;

    protected $fillable = [
        'booking_id',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'phone',
        'date_of_birth',
        'gender',
        'passport_number',
        'passport_expiry',
        'passport_copy',
        'nationality',
        'traveler_type',
        'special_needs',
        'emergency_contact',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'passport_expiry' => 'date',
        'emergency_contact' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Accessors
     */
    public function getFullNameAttribute(): string
    {
        $name = $this->first_name;
        
        if ($this->middle_name) {
            $name .= ' ' . $this->middle_name;
        }
        
        $name .= ' ' . $this->last_name;
        
        return $name;
    }

    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    public function getPassportUrlAttribute(): ?string
    {
        if (!$this->passport_copy) {
            return null;
        }

        return Storage::disk('passports')->url($this->passport_copy);
    }

    public function getIsPassportValidAttribute(): bool
    {
        if (!$this->passport_expiry) {
            return false;
        }

        return $this->passport_expiry > now();
    }

    public function getIsAdultAttribute(): bool
    {
        return $this->age >= 18;
    }

    /**
     * Scopes
     */
    public function scopeAdults($query)
    {
        return $query->where('traveler_type', 'adult');
    }

    public function scopeChildren($query)
    {
        return $query->where('traveler_type', 'child');
    }

    public function scopeInfants($query)
    {
        return $query->where('traveler_type', 'infant');
    }

    public function scopeWithValidPassport($query)
    {
        return $query->whereNotNull('passport_number')
                     ->where('passport_expiry', '>', now());
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        // Delete passport file when traveler is deleted
        static::deleting(function ($traveler) {
            if ($traveler->passport_copy) {
                Storage::disk('passports')->delete($traveler->passport_copy);
            }
        });
    }
}