<?php

namespace App\Models;

use App\Models\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PaymentTransaction extends Model
{
    use HasFactory, UsesUuid, SoftDeletes;

    protected $fillable = [
        'transaction_reference',
        'booking_id',
        'user_id',
        'payment_gateway',
        'gateway_reference',
        'gateway_transaction_id',
        'amount',
        'currency',
        'type',
        'status',
        'payment_method',
        'card_type',
        'card_last4',
        'bank_name',
        'gateway_response',
        'metadata',
        'ip_address',
        'notes',
        'paid_at',
        'failed_at',
        'failure_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_response' => 'array',
        'metadata' => 'array',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (empty($transaction->transaction_reference)) {
                $transaction->transaction_reference = self::generateReference();
            }
        });
    }

    /**
     * Generate unique transaction reference
     */
    public static function generateReference(): string
    {
        do {
            $reference = 'TXN-' . date('Ymd') . '-' . strtoupper(Str::random(8));
        } while (self::where('transaction_reference', $reference)->exists());

        return $reference;
    }

    /**
     * Relationships
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Accessors
     */
    public function getIsSuccessfulAttribute(): bool
    {
        return $this->status === 'success';
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->status === 'pending';
    }

    public function getIsFailedAttribute(): bool
    {
        return in_array($this->status, ['failed', 'cancelled']);
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2);
    }

    /**
     * Scopes
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->whereIn('status', ['failed', 'cancelled']);
    }

    public function scopeForBooking($query, string $bookingId)
    {
        return $query->where('booking_id', $bookingId);
    }

    public function scopeByGateway($query, string $gateway)
    {
        return $query->where('payment_gateway', $gateway);
    }
}