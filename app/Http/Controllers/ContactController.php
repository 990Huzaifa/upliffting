<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try{
            $query = Contact::orderBy('id', 'desc');
            $perPage = $request->query('per_page', 25);
            $searchQuery = $request->query('search');
            $role = $request->query('role'); // riders, customers, all
            if (!empty($searchQuery)) {
                $query->where('full_name', 'like', '%' . $searchQuery . '%');
            }
            if (!empty($role) && $role != 'all') {
                $query->where('role', $role);
            }
            $data = $query->paginate($perPage);
            return response()->json($data, 200);
        }catch(QueryException $e){
            return response()->json(['DB error' => $e->getMessage()], 500);

        }catch(Exception $e){
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try{
            $user = Auth::user();

            $data = Contact::create([
                'user_id' => $user->id,
                'full_name' => $request->full_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'message' => $request->message,
                'role' => $user->role,
            ]);
            return response()->json(['data' => $data], 200);

        }catch(QueryException $e){
            return response()->json(['DB error' => $e->getMessage()]);
        }catch(Exception $e){
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try{
            $contact = Contact::find($id);
            return response()->json($contact, 200);
        }catch(QueryException $e){
            return response()->json(['DB error' => $e->getMessage()]);
        }catch(Exception $e){
            return response()->json(['error' => $e->getMessage()]);   
        }
    }


}
