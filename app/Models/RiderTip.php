<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiderTip extends Model
{
    use HasFactory;

    protected $fillable = [
        'ride_id',
        'rider_id',
        'percent',
        'amount',
    ];
}
