<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RidesDropOff extends Model
{
    use HasFactory;


    protected $fillable = [
        'ride_id',
        'drop_location',
    ];

}
