<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RatingReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'ride_id',
        'customer_id',
        'rider_id',
        'rating',
        'send_by',
        'review',
    ];
}
