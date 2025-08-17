<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rider extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'license_number',
        'license_expiry',
        'driving_experience',
        'license_photo',
        'total_rides',
        'current_rating',
        'background_verfied',
        'status',
        'is_pet',
    ];
}
