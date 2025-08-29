<?php

namespace App\Http\Controllers\Rider;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;

class RideController extends Controller
{
    public function acceptRide(Request $request): JsonResponse
    {
        try{
            $user = Auth::user();

            return response()->json(['message' => 'Ride accepted successfully'], 200);
        } catch (QueryException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
        

    }
}
