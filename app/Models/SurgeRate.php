<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurgeRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_type_rate_id',
        'start_time',
        'end_time',
        'surge_rate',
        'day_of_week',
    ];
}
