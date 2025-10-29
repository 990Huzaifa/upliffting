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
use App\Models\VehicleInspection;
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

    public function getVeirficationInfo(): JsonResponse
    {
        try{
            $user = Auth::user();
            $data = User::select('riders.*','users.*','users.status as account_status')
            ->join('riders', 'users.id', '=', 'riders.user_id')
            ->where('users.id', $user->id)->first();

            $vehicle = Vehicle::where('vehicle_of', $user->id)->where('is_driving', true)->first();


            $vehicle_inspection = VehicleInspection::where('vehicle_id', $vehicle->id)->first();
            if(empty($vehicle_inspection)){
                $vehicle_inspection = null;
            }else{
                if (
                $vehicle_inspection->headlights == null || 
                $vehicle_inspection->airlights == null || 
                $vehicle_inspection->indicators == null || 
                $vehicle_inspection->stop_lights == null || 
                $vehicle_inspection->windshield == null || 
                $vehicle_inspection->windshield_wipers == null || 
                $vehicle_inspection->safty_belt == null || 
                $vehicle_inspection->tires == null || 
                $vehicle_inspection->speedometer == null
            ) $vehicle_inspection = null;
            }
            return response()->json(['user' => $data, 'vehicle' => $vehicle, 'vehicle_inspection' => $vehicle_inspection], 200);
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


    // public function stripeOnboardingLink(Request $request): JsonResponse
    // {
    //     try{
    //         $user = Auth::user();
    //         $stripAcoountId = Rider::where('user_id',$user->id)->value('stripe_account_id');

    //         $stripeService = new StripeService();
    //         $link = $stripeService->createOnboardingLink($stripAcoountId,$user->id);
    //         return response()->json(['onboarding_link' => $link], 200);
    //     }catch(Exception $e){
    //         return response()->json(['error' => $e->getMessage()], 500);
    //     }
    // }

    // public function refreshOnboardingLink($id)
    // {
    //     try{
    //         $riderAccountId = Rider::where('user_id',$id)->value('stripe_account_id');
    //         $stripeService = new StripeService();
    //         $link = $stripeService->createOnboardingLink($riderAccountId, $id);
    //         return response()->json(['url' => $link], 200);
    //     }catch(Exception $e){
    //         return response()->json(['error' => $e->getMessage()], 500);
    //     }
    // }

    // public function successOnboardingLink($id)
    // {
    //     try{
    //         $riderAccountId = Rider::where('user_id',$id)->value('stripe_account_id');

    //         $stripeService = new StripeService();

    //         $accountStatus = $stripeService->retrieveAccount($riderAccountId);
    //         if($accountStatus['success'] == true){
    //             Rider::where('user_id', $id)->update([
    //                 'is_stripe_verified' => $accountStatus['is_verified']
    //             ]);
    //         }
    //         // return response()->json(['message' => 'Onboarding completed successfully'], 200);
    //         return response('
    //             <html>
    //                 <head><title>Stripe Onboarding</title></head>
    //                 <body style="text-align:center; margin-top:50px;">
    //                     <h2>✅ Onboarding Completed</h2>
    //                     <p>You can close this tab. Redirecting you to the app...</p>
    //                     <script>
    //                         setTimeout(function(){
    //                             window.location.href = "myapp://onboarding/success";
    //                         }, 1000);
    //                     </script>
    //                 </body>
    //             </html>
    //             ', 200)->header('Content-Type', 'text/html');
    //     }catch(Exception $e){
    //         return response()->json(['error' => $e->getMessage()], 500);
    //     }
    // }

    public function addBank(Request $request): JsonResponse
    {
        try{
            $user = Auth::user();
            DB::beginTransaction();  
            $validator = Validator::make($request->all(),[
                'stripe_account_id' => 'required',
                'bank_token' => 'required',
            ],[
                'stripe_account_id.required' => 'Stripe account ID is required',
                'bank_token.required' => 'Bank token is required',
            ]);
            
            if($validator->fails())throw new Exception($validator->errors()->first(),400);
            $acc_id = $request->stripe_account_id;
            $token = $request->bank_token;
            $stripeService = new StripeService();
            $response = $stripeService->addBankAccount($acc_id, $token);

            DB::commit();
            return response()->json(['data' => $response], 200);
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
                'stripe_account_id' => 'required',
                'social_security_number' => 'required|numeric',
            ],[
                'stripe_account_id.required' => 'Stripe account ID is required',
                'social_security_number.required' => 'Social security number is required',
                'social_security_number.numeric' => 'Social security number must be numeric',
            ]);
            
            if($validator->fails())throw new Exception($validator->errors()->first(),400);

            $user->update([
                'social_security_number' => $request->social_security_number
            ]);
            $acc_id = $request->stripe_account_id;
            $stripeService = new StripeService();
            $response = $stripeService->updateSSN($acc_id,$user->first_name,$user->last_name, $request->social_security_number);
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

    public function tosAcceptance(Request $request): JsonResponse
    {
        try{
            $user = Auth::user();
            DB::beginTransaction();  
            $validator = Validator::make($request->all(),[
                'stripe_account_id' => 'required',
            ],[
                'stripe_account_id.required' => 'Stripe account ID is required',
            ]);
            
            if($validator->fails())throw new Exception($validator->errors()->first(),400);

            $acc_id = $request->stripe_account_id;
            $ip = $request->ip();
            $stripeService = new StripeService();
            $response = $stripeService->tosAcceptance($acc_id, $ip);
            DB::commit();
            return response()->json(['data' => $response], 200);

        }catch(QueryException $e){
            DB::rollBack();
            return response()->json(['DB error' => $e->getMessage()], 500);
        }catch(Exception $e){
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], $e->getCode());
        }
    }


    
}
