<?php

namespace App\Models;

use App\Models\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Model;

class PackageAvailability extends Model
{
    use UsesUuid;

    protected $table = 'package_availability';

    protected $fillable = ['travel_package_id', 'date', 'available_slots'];
    
    protected $casts = [
        'date' => 'date',
    ];
}