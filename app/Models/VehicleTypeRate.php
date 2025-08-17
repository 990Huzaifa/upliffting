<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleTypeRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_type_id',
        'base_price',
        'price_per_km',
        'price_per_min',
        'booking_fee',
        'country_id',
        'state_id',
        'city_id',
    ];
}
