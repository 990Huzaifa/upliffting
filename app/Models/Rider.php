<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rider extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'stripe_account_id',
        'is_stripe_verified',
        'license_number',
        'license_expiry',
        'driving_experience',
        'license_photo',
        'total_rides',
        'current_rating',
        'background_verfied',
        'online_status',
        'is_pet',
    ];
}
