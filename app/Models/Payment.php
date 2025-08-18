<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'ride_id',
        'customer_id',
        'rider_id',
        'amount',
        'payment_method_id',
        'transaction_id',
        'status',
    ];
}
