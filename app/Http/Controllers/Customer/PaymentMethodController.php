<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\UserAccount;
use App\Services\StripeService;
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

    // STRIP APIS

    public function setupIntent(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $customerId = Customer::where('user_id', $user->id)->value('stripe_customer_id');
            $stripeService = new StripeService();
            $clientSecret = $stripeService->steupIntent($customerId);

            return response()->json(['client_secret' => $clientSecret], 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function attachPaymentMethod(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $validator = Validator::make($request->all(), [
                'stripe_payment_method_id' => 'required',
                'card_number' => 'required|string|max:16',
            ]);

            if ($validator->fails()) {
                throw new Exception($validator->errors()->first(), 422);
            }
            $customerId = Customer::where('user_id', $user->id)->value('stripe_customer_id');
            
            $paymentMethodId = $request->input('stripe_payment_method_id');
            $stripeService = new StripeService();
            $result = $stripeService->attachPaymentMethodToCustomer($customerId, $paymentMethodId);

            if ($result['success']) {

                UserAccount::create([
                    'user_id' => $user->id,
                    'stripe_payment_method_id' => $paymentMethodId,
                    'card_number' => '**** **** **** ' . substr($request->input('card_number'), -4),
                    'is_default' => true,
                ]);

                return response()->json(['message' => $result['message']], 200);
            } else {
                return response()->json(['error' => $result['error']], 500);
            }
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    public function switchAccount(string $id): JsonResponse
    {
        try{
            $user = Auth::user();
            $userAccount = UserAccount::where('id', $id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            if($userAccount->is_default) throw new Exception('This payment method is already set as default.', 400);
            // then set the selected payment method as default
            if (!$userAccount) throw new Exception('Payment method not found.', 404);
            
            $customerId = Customer::where('user_id', $user->id)->value('stripe_customer_id');
            // strip side start
            $stripeService = new StripeService();
            $stripeService->setDefaultPaymentMethod(
                $customerId,
                $userAccount->stripe_payment_method_id
            );
            // stripe side end
            UserAccount::where('user_id', $user->id)->update(['is_default' => false]);
            $userAccount->update(['is_default' => true]);

            return response()->json(['message' => 'Payment method updated successfully.'], 200);
        }catch(QueryException $e){
            return response()->json(['DB error' => $e->getMessage()], 500);
        }catch(Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
