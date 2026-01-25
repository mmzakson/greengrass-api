<?php

namespace App\Models;

use App\Models\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use UsesUuid, SoftDeletes;

    protected $fillable = ['user_id', 'travel_package_id', 'booking_reference'];
}