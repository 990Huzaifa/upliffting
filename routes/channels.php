<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.hogiya push
*/

Broadcast::channel('customer.{customerId}', function ($user, $customerId) {
    return (int)$user->id === (int)$customerId;
});

Broadcast::channel('rider.{riderId}', function ($user, $riderId) {
    return (int)$user->id === (int)$riderId;
});

Broadcast::channel('ride.{rideId}', function ($user, $rideId) {
    // allow rider or customer of this ride; implement your check here
    return true;
});