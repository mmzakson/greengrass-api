<?php

namespace App\Models;

use App\Models\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Booking extends Model
{
    use HasFactory, UsesUuid, SoftDeletes;

    protected $fillable = [
        'booking_reference',
        'user_id',
        'travel_package_id',
        'guest_email',
        'guest_phone',
        'guest_first_name',
        'guest_last_name',
        'number_of_travelers',
        'number_of_adults',
        'number_of_children',
        'travel_date',
        'total_amount',
        'amount_paid',
        'amount_due',
        'payment_status',
        'booking_status',
        'special_requests',
        'notes',
        'confirmed_at',
        'cancelled_at',
        'cancellation_reason',
        'cancelled_by',
    ];

    protected $casts = [
        'travel_date' => 'date',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'amount_due' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($booking) {
            if (empty($booking->booking_reference)) {
                $booking->booking_reference = self::generateBookingReference();
            }
        });
    }

    /**
     * Generate unique booking reference
     */
    public static function generateBookingReference(): string
    {
        do {
            $reference = 'BLT-' . date('Ymd') . '-' . strtoupper(Str::random(6));
        } while (self::where('booking_reference', $reference)->exists());

        return $reference;
    }

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function travelPackage()
    {
        return $this->belongsTo(TravelPackage::class, 'travel_package_id');
    }

    public function travelers()
    {
        return $this->hasMany(Traveler::class);
    }

    public function paymentTransactions()
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function cancelledBy()
    {
        return $this->belongsTo(Admin::class, 'cancelled_by');
    }

    /**
     * Accessors
     */
    public function getCustomerNameAttribute(): string
    {
        if ($this->user_id) {
            return $this->user->full_name;
        }
        return "{$this->guest_first_name} {$this->guest_last_name}";
    }

    public function getCustomerEmailAttribute(): ?string
    {
        return $this->user_id ? $this->user->email : $this->guest_email;
    }

    public function getCustomerPhoneAttribute(): ?string
    {
        return $this->user_id ? $this->user->phone : $this->guest_phone;
    }

    public function getIsGuestBookingAttribute(): bool
    {
        return is_null($this->user_id);
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function getIsConfirmedAttribute(): bool
    {
        return $this->booking_status === 'confirmed';
    }

    public function getIsCancelledAttribute(): bool
    {
        return $this->booking_status === 'cancelled';
    }

    public function getCanBeCancelledAttribute(): bool
    {
        return in_array($this->booking_status, ['pending', 'confirmed']) 
               && $this->travel_date > now()->addDays(3);
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('booking_status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('booking_status', 'confirmed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('booking_status', 'cancelled');
    }

    public function scopeCompleted($query)
    {
        return $query->where('booking_status', 'completed');
    }

    public function scopeByPaymentStatus($query, string $status)
    {
        return $query->where('payment_status', $status);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('travel_date', '>', now())
                    ->whereIn('booking_status', ['confirmed', 'pending']);
    }

    public function scopePast($query)
    {
        return $query->where('travel_date', '<', now());
    }

    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeGuestBookings($query)
    {
        return $query->whereNull('user_id');
    }
}