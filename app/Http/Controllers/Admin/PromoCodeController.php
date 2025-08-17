<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Session;

class PromoCodeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try{
            
            $query = PromoCode::orderBy('id', 'desc');

            $perPage = $request->query('per_page', 25);
            $searchQuery = $request->query('search');

            if (!empty($searchQuery)) {
                $customerIds = PromoCode::where('code', 'like', '%' . $searchQuery . '%')
                        ->toArray();
    
                    // Filter orders by the found Pomo codes IDs
                    $query = $query->whereIn('users.id', $customerIds);
            }
            // Execute the query with pagination
            $data = $query->paginate($perPage);

            return view('admin.promo-codes.index', compact('data'));

        }catch(Exception $e){
            Session::flash('error', [
                'text' => $e->getMessage(),
            ]);
            return redirect()->back();
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.promo-code.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try{
            $validdator = Validator::make($request->all(),[
                
            ]);

            if ($validdator->fails()) throw new Exception($validdator->errors()->first(),400);
            

        }catch(Exception $e){
            Session::flash('error', [
                'text' => $e->getMessage(),
            ]);
            return redirect()->back();
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
