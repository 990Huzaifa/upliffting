<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Page;
use App\Models\User;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function about(): JsonResponse
    {   
        try{
            $data = Page::where('title', 'about')->where('role', 'rider')->first();
            return response()->json($data,200);
        }catch(QueryException $e){
            return response()->json(['DB error' => $e->getMessage()], 500);
        }catch(Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function pnp(): JsonResponse
    {   
        try{
            $data = Page::where('title', 'pnp')->where('role', 'rider')->first();
            return response()->json($data,200);
        }catch(QueryException $e){
            return response()->json(['DB error' => $e->getMessage()], 500);
        }catch(Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function tnc(): JsonResponse
    {   
        try{
            $data = Page::where('title', 'tnc')->where('role', 'rider')->first();
            return response()->json($data,200);
        }catch(QueryException $e){
            return response()->json(['DB error' => $e->getMessage()], 500);
        }catch(Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function contactStore(Request $request): JsonResponse
    {
        try{
            $user = Auth::user();

            $validator = Validator::make($request->all(),[
                'full_name' => 'required',
                'email' => 'required|email',
                'phone' => 'required',
                'message' => 'required',
            ],[
                'full_name.required' => 'Full name is required',
                'email.required' => 'Email is required',
                'email.email' => 'Invalid email format',
                'phone.required' => 'Phone number is required',
                'message.required' => 'Message is required',
            ]);

            if($validator->fails())throw new Exception($validator->errors()->first(),400);

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
    public function profile(): JsonResponse
    {
        try{
            $user = Auth::user();
            $data = User::select('users.first_name','users.last_name','users.email','users.phone','users.avatar')
            ->join('customers', 'users.id', '=', 'customers.user_id')
            ->where('users.id', $user->id)->first();
            return response()->json(['user' => $data], 200);
        }catch(QueryException $e){
            return response()->json(['DB error' => $e->getMessage()], 500);
        }catch(Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
