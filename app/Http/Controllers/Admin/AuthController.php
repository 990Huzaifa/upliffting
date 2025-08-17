<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Exception;
use Hash;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(){
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.signin');
    }
    public function signin(Request $request): JsonResponse
    {
        try{
            
            $validator = Validator::make($request->all(),[
                'email' => 'required|email',
                'password' => 'required',
            ],[
                'email.required' => 'Email is required',
                'email.email' => 'Invalid email format',
                'password.required' => 'Password is required',
            ]);

            if ($validator->fails()) throw new Exception($validator->errors()->first(), 400);
            
            
            // Conditions
            if (!Admin::where('email', $request->email)->exists())throw new Exception('Invalid email address or password', 400);


            $admin = Admin::where('email', $request->email)->first();
            if (!Hash::check($request->password, $admin->password)) throw new Exception('Invalid email address or password', 400);

            // $admin->tokens()->delete();
            $token = $admin->createToken('admin-token', ['admin'])->plainTextToken;

            return response()->json([
                'token' => $token,
                'admin' => $admin,
            ], 200);
    

        }catch(QueryException $e){
            return response()->json(['DB error' => $e->getMessage(),], 403);
        }catch(Exception $e){
            return response()->json(['server error' => $e->getMessage(),], 500);
        }
    }

    public function logout(Request $request)
    {
        try{
            $admin = Auth::guard('admin')->user();
            $admin->tokens()->delete();
            return response()->json(['message' => 'Logout successfully'], 200);
        }catch(Exception $e){
            return response()->json(['server error' => $e->getMessage(),], 500);
        }
    }
}
