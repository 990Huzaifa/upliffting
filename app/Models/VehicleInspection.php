<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleInspection extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'headlights',
        'is_headlights',
        'airlights',
        'is_airlights',
        'indicators',
        'is_indicators',
        'stop_lights',
        'is_stop_lights',
        'windshield',
        'is_windshield',
        'windshield_wipers',        
        'is_windshield_wipers',
        'safty_belt',
        'is_safty_belt',
        'tires',
        'is_tires',
        'speedometer',
        'is_speedometer',
    ];
}
