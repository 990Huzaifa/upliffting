<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\State;
use App\Models\VehicleTypeRate;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class VehicleTypeRateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try{            
            $admin = Auth::guard('admin')->user();

            // filter by country state city
            $country = $request->query('country');
            $state = $request->query('state');
            $city = $request->query('city');


            $query = VehicleTypeRate::select('vehicle_type_rates.*', 'countries.name as country_name', 'states.name as state_name', 'cities.name as city_name', 'vehicle_types.title as vehicle_type_title','vehicle_types.icon as icon')
            ->join('countries', 'vehicle_type_rates.country_id', '=', 'countries.id')
            ->join('states', 'vehicle_type_rates.state_id', '=', 'states.id')
            ->join('cities', 'vehicle_type_rates.city_id', '=', 'cities.id')
            ->join('vehicle_types', 'vehicle_type_rates.vehicle_type_id', '=', 'vehicle_types.id')
            ->orderBy('id', 'desc');

            $perPage = $request->query('per_page', 25);
            $searchQuery = $request->query('search');

            if ($searchQuery !== '') {
                $query->where(function ($sub) use ($searchQuery) {
                    $like = "%{$searchQuery}%";
                    $sub->where('countries.name', 'LIKE', $like)
                        ->orWhere('states.name', 'LIKE', $like)
                        ->orWhere('cities.name', 'LIKE', $like);
                });
            }
            // Execute the query with pagination
            $data = $query->paginate($perPage);

            return response()->json($data, 200);

        }catch(Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {   
        try{
            DB::beginTransaction();

            $validator = Validator::make($request->all(),[
                'vehicle_type_id' => 'required|exists:vehicle_types,id',
                'booking_fee' => 'required',
                'base_price' => 'required',
                'price_per_km' => 'required',
                'price_per_min' => 'required',
                'country_id' => 'required',
                'state_id' => 'required',
                'city_id' => 'required',
            ],[
                'vehicle_type_id.required' => 'Vehicle type is required',
                'vehicle_type_id.exists' => 'Vehicle type does not exist',
                'booking_fee.required' => 'Booking fee is required',
                'base_price.required' => 'Base price is required',
                'price_per_km.required' => 'Price per km is required',
                'price_per_min.required' => 'Price per min is required',
                'country_id.required' => 'Country is required',
                'state_id.required' => 'State is required',
                'city_id.required' => 'City is required',
            ]);
            if ($validator->fails()) throw new Exception($validator->errors()->first(), 400);

            $data = VehicleTypeRate::create([
                'vehicle_type_id' => $request->vehicle_type_id,
                'booking_fee' => $request->booking_fee,
                'base_price' => $request->base_price,
                'price_per_km' => $request->price_per_km,
                'price_per_min' => $request->price_per_min,
                'country_id' => $request->country_id,
                'state_id' => $request->state_id,
                'city_id' => $request->city_id,
            ]);

            DB::commit();
            return response()->json($data, 200);

        }catch(Exception $e){
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id, Request $request): JsonResponse
    {
        try{
            $data = VehicleTypeRate::find($id);
            return response()->json($data,200);

        }catch(QueryException $e){
            return response()->json(['DB error' => $e->getMessage()], 500);
        }catch(Exception $e){
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try{
            DB::beginTransaction();
            
            $validator = Validator::make($request->all(),[
                'vehicle_type_id' => 'required|exists:vehicle_types,id',
                'booking_fee' => 'required',
                'country_id' => 'required',
                'city_id' => 'required',
                'state_id' => 'required',
                'base_price' => 'required',
                'price_per_km' => 'required',
                'price_per_min' => 'required',
            ],[
                'vehicle_type_id.required' => 'Vehicle type is required',
                'vehicle_type_id.exists' => 'Vehicle type does not exist',
                'booking_fee.required' => 'Booking fee is required',
                'country_id.required' => 'Country is required',
                'city_id.required' => 'City is required',
                'state_id.required' => 'State is required',
                'base_price.required' => 'Base price is required',
                'price_per_km.required' => 'Price per km is required',
                'price_per_min.required' => 'Price per min is required',
            ]);

            if ($validator->fails())throw new Exception($validator->errors()->first(),400);

            $data = VehicleTypeRate::find($id);
            if(empty($data)) throw new Exception("data not found", 400);


            
            $data->update([
                'vehicle_type_id' => $request->vehicle_type_id,
                'booking_fee' => $request->booking_fee,
                'base_price' => $request->base_price,
                'price_per_km' => $request->price_per_km,
                'price_per_min' => $request->price_per_min,
                'country_id' => $request->country_id,
                'state_id' => $request->state_id,
                'city_id' => $request->city_id,
            ]);

            DB::commit();
            return response()->json($data, 200);

        }catch(QueryException $e){
            DB::rollBack();
            return response()->json(['DB error' => $e->getMessage()], 500);
        }catch(Exception $e){
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function statesByCountry($id): JsonResponse
    {
        try{
            $data = State::where('country_id', $id)->get();
            return response()->json($data,200);
        }catch(Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function cityByState($id): JsonResponse
    {
        try{
            $data = City::where('state_id', $id)->get();
            return response()->json($data,200);
        }catch(Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function list1(Request $request): JsonResponse
    {
        // get ip
        $request->ip();
        // get location
        $location = getLocationByIP($request->ip());
        $countryName = $location['country'];
        $stateName = $location['state'];
        $cityName = $location['city'];
        // Fetch all vehicle types and their pricing
        $data = VehicleTypeRate::select('vehicle_type_rates.id','vehicle_types.title')
        ->join('vehicle_types', 'vehicle_type_rates.vehicle_type_id', '=', 'vehicle_types.id')
        ->join('countries', 'vehicle_type_rates.country_id', '=', 'countries.id')
            ->join('states', 'vehicle_type_rates.state_id', '=', 'states.id')
            ->join('cities', 'vehicle_type_rates.city_id', '=', 'cities.id')
            ->when($countryName, function ($q) use ($countryName) {
                $q->where('countries.name', 'LIKE', "%$countryName%");
            })
            ->when($stateName, function ($q) use ($stateName) {
                $q->where('states.name', 'LIKE', "%$stateName%");
            })
            ->when($cityName, function ($q) use ($cityName) {
                $q->where('cities.name', 'LIKE', "%$cityName%");
            })
        ->get();
        return response()->json($data,200);
    }
    public function list2(): JsonResponse
    {
        $data = VehicleTypeRate::select('vehicle_type_rates.*', 'vehicle_types.title as vehicle_type_title','vehicle_types.icon as icon')
        ->join('vehicle_types', 'vehicle_type_rates.vehicle_type_id', '=', 'vehicle_types.id')
        ->get();
        return response()->json($data,200);
    }

    public function country(): JsonResponse
    {
        $data = Country::all();
        return response()->json($data,200);
    }

    public function state(): JsonResponse
    {
        $data = State::all();
        return response()->json($data,200);
    }
    public function city(): JsonResponse
    {
        $data = City::all();
        return response()->json($data,200);
    }

    public function currency(): JsonResponse
    {
        $data = Currency::all();
        return response()->json($data,200);
    }
}
