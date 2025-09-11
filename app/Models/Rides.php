<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rides extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'vehicle_type_rate_id',
        'pickup_location',
        "pickup_lat",
        "pickup_lng",
        'status',
        'dropoff_location',
        'base_fare',
        'discount_amount',
        'distance',
        'duration',
        'final_fare',
        'current_rating',
        'rider_id',
        'vehicle_id',
        'promo_code_id',
        'reason',
        'cancelled_by',
        'cancel_by_role'
    ];
}
