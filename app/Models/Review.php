<?php

namespace App\Models;

use App\Models\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use UsesUuid, SoftDeletes;

    protected $fillable = ['user_id', 'travel_package_id', 'rating', 'comment', 'is_approved'];
    
    protected $casts = [
        'is_approved' => 'boolean',
    ];
}