<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\UserAccount;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PaymentMethodController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try{
            $user = Auth::user();
            $data = UserAccount::where('user_id', $user->id)->get();
            // in this data we need to make change in the reposne where card_number we need to masked it like **** **** **** 1234
            $data->transform(function ($item) {
                $item->card_number = '**** **** **** ' . substr($item->card_number, -4);
                return $item;
            });

            return response()->json($data, 200);
        }catch(QueryException $e){
            return response()->json(['DB error' => $e->getMessage()], 500);
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
            $user = Auth::user();
            $validator = Validator::make($request->all(), [
                'card_holder_name' => 'required|string|max:255',
                'card_number' => 'required|string|max:16',
                'expiry_date' => 'required|date_format:m/y',
                'cvv' => 'required|string|max:4',
                'type' => 'required|in:debit,credit',
            ]);

            if ($validator->fails()) {
                throw new Exception($validator->errors()->first(), 422);
            }

            $userAccount = UserAccount::create([
                'user_id' => $user->id,
                'card_holder_name' => $request->card_holder_name,
                'card_number' => $request->card_number,
                'expiry_date' => $request->expiry_date,
                'cvv' => $request->cvv,
                'type' => $request->type,
            ]);

            return response()->json($userAccount, 201);
        } catch (QueryException $e) {
            return response()->json(['DB error' => $e->getMessage()], 500);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
        
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
