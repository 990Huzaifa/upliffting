<?php

namespace App\Http\Controllers\Rider;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Page;
use App\Models\Rider;
use App\Models\User;
use App\Models\UserAccount;
use App\Models\UserBank;
use App\Models\Vehicle;
use App\Services\StripeService;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function profile(): JsonResponse
    {
        try{
            $user = Auth::user();
            $data = User::select('riders.*','users.*','users.status as account_status')
            ->join('riders', 'users.id', '=', 'riders.user_id')
            ->where('users.id', $user->id)->first();
            return response()->json(['user' => $data], 200);
        }catch(QueryException $e){
            return response()->json(['DB error' => $e->getMessage()], 500);
        }catch(Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function editProfile(Request $request): JsonResponse
    {
        try{
            $rider = Auth::user();
            DB::beginTransaction();
            $validator = Validator::make(request()->all(),[
                'first_name' => 'required',
                'last_name' => 'required',
                'avatar' => 'nullable',
            ],[
                'first_name.required' => 'First name is required',
                'last_name.required' => 'Last name is required',
            ]);

            if($validator->fails())throw new Exception($validator->errors()->first(),400);

            $avatar = $rider->avatar;

            if ($request->hasFile('avatar')) {
                $image = $request->file('avatar');
                $image_name = 'r-avatar' . time() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('rider-avatar'), $image_name);
                $avatar = 'rider-avatar/' . $image_name;

                // Delete the previous avatar if it exists
                if ($rider->avatar && file_exists(public_path($rider->avatar))) {
                    unlink(public_path($rider->avatar));
                }
            }
            $rider->update([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'avatar' => $avatar
            ]);
            DB::commit();
            return response()->json(['user' => $rider], 200);
        }catch(QueryException $e){
            DB::rollBack();
            return response()->json(['DB error' => $e->getMessage()], 500);
        }catch(Exception $e){
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function addCard(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            DB::beginTransaction();
            $validator = Validator::make($request->all(), [
                'stripe_payment_method_id' => 'required',
                'card_number' => 'required|numeric',
            ], [
                'stripe_payment_method_id.required' => 'Stripe payment method ID is required',
                'card_number.required' => 'Card number is required',
                'card_number.numeric' => 'Card number must be numeric',
            ]);

            if ($validator->fails()) throw new Exception($validator->errors()->first(), 400);

            UserAccount::create([
                'user_id' => $user->id,
                'card_number' => $request->card_number,
                'stripe_payment_method_id' => $request->stripe_payment_method_id,
            ]);

            DB::commit();
            return response()->json(['user' => $user], 200);
        } catch (QueryException $e) {
            DB::rollBack();
            return response()->json(['DB error' => $e->getMessage()], 500);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], is_int($e->getCode()) ? $e->getCode() : 500);
        }
    }

    public function addBank(Request $request): JsonResponse
    {
        try{
            $user = Auth::user();
            DB::beginTransaction();  
            $validator = Validator::make($request->all(),[
                'bank_name' => 'required',
                'account_holder_name' => 'required',
                'account_number' => 'required|numeric',
                'account_type' => 'required',
                'routing_number' => 'required',
            ],[
                'bank_name.required' => 'Bank name is required',
                'account_holder_name.required' => 'Account name is required',
                'account_number.required' => 'Account number is required',
                'account_number.numeric' => 'Account number must be numeric',
                'account_type.required' => 'Account type is required',
                'routing_number.required' => 'Routing number is required',
            ]);
            
            if($validator->fails())throw new Exception($validator->errors()->first(),400);

            $data = UserBank::create([
                'user_id' => $user->id,
                'bank_name' => $request->bank_name,
                'account_holder_name' => $request->account_holder_name,
                'account_number' => $request->account_number,
                'account_type' => $request->account_type,
                'routing_number' => $request->routing_number,
            ]);

            DB::commit();
            return response()->json(['data' => $data], 200);
        }catch(QueryException $e){
            DB::rollBack();
            return response()->json(['DB error' => $e->getMessage()], 500);
        }catch(Exception $e){
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], $e->getCode());
        }
    }

    public function addSSN(Request $request): JsonResponse
    {
        try{
            $user = Auth::user();
            DB::beginTransaction();  
            $validator = Validator::make($request->all(),[
                'social_security_number' => 'required|numeric',
            ],[
                'social_security_number.required' => 'Social security number is required',
                'social_security_number.numeric' => 'Social security number must be numeric',
            ]);
            
            if($validator->fails())throw new Exception($validator->errors()->first(),400);

            $user->update([
                'social_security_number' => $request->social_security_number
            ]);
            DB::commit();
            return response()->json(['user' => $user], 200);

        }catch(QueryException $e){
            DB::rollBack();
            return response()->json(['DB error' => $e->getMessage()], 500);
        }catch(Exception $e){
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], $e->getCode());
        }
    }

    public function goOnline(Request $request): JsonResponse
    {
        try{
            $user = Auth::user();

            Rider::where('user_id', $user->id)->update([
                'online_status' => $request->status
            ]);
            return response()->json(['message' => 'status updated successfully'], 200);
        }catch(QueryException $e){
            return response()->json(['DB error' => $e->getMessage()], 500);
        }catch(Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

        // settings

    public function Pet(Request $request): JsonResponse
    {
        try{
            $user = Auth::user();
            Rider::where('user_id', $user->id)->update([
                'is_pet' => $request->is_pet
            ]);
            return response()->json(['user' => $user], 200);
        }catch(QueryException $e){
            return response()->json(['DB error' => $e->getMessage()], 500);
        }catch(Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function activateVehicle($id): JsonResponse
    {
        try{
            $user = Auth::user();

            // make all vehicles inactive
            Vehicle::where('user_id', $user->id)->update([
                'is_driving' => false
            ]);

            // make vehicle active
            $data = Vehicle::where('id', $id)->update([
                'is_driving' => true
            ]);

            return response()->json(['data' => $data,'message' => 'vehicle activated successfully'], 200);
        }catch(QueryException $e){
            return response()->json(['DB error' => $e->getMessage()], 500);
        }catch(Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

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

    public function updateLatLong(Request $request): JsonResponse
    {
        try{
            $user = Auth::user();
            $rider = User::find($user->id);
            DB::beginTransaction();
            $rider->lat = $request->lat;
            $rider->lng = $request->lng;
            $rider->save();
            DB::commit();
            return response()->json(['message' => 'Location updated successfully', "data" => $rider], 200);
        }catch(QueryException $e){
            DB::rollBack();
            return response()->json(['DB error' => $e->getMessage()], 500);
        }catch(Exception $e){
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }



    // strip function for rider


    public function stripeOnboardingLink(Request $request): JsonResponse
    {
        try{
            $user = Auth::user();
            $stripAcoountId = Rider::where('user_id',$user->id)->value('stripe_account_id');

            $stripeService = new StripeService();
            $link = $stripeService->createOnboardingLink($stripAcoountId);
            return response()->json(['onboarding_link' => $link], 200);
        }catch(Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function refreshOnboardingLink(Request $request, $riderAccountId): JsonResponse
    {
        try{
            $stripeService = new StripeService();
            $link = $stripeService->createOnboardingLink($riderAccountId);
            return response()->json(['onboarding_link' => $link], 200);
        }catch(Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function successOnboardingLink(Request $request, $riderAccountId): JsonResponse
    {
        try{
            $user = Auth::user();
            $stripAcoountId = Rider::where('user_id',$user->id)->value('stripe_account_id');

            $stripeService = new StripeService();

            $accountStatus = $stripeService->retrieveAccount($stripAcoountId);
            if($accountStatus['success'] == true){
                Rider::where('user_id', $user->id)->update([
                    'is_stripe_verified' => $accountStatus['is_verified']
                ]);
            }
            return response()->json(['message' => 'Onboarding completed successfully'], 200);
        }catch(Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
