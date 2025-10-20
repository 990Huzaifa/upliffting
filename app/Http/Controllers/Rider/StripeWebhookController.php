<?php

namespace App\Http\Controllers\Rider;

use App\Http\Controllers\Controller;
use App\Models\Rider;
use Illuminate\Http\Request;
use Stripe\Account;
use Stripe\Stripe;


class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $event = $request->all();

        if ($event['type'] === 'account.updated') {
            $account = $event['data']['object'];

            if ($account['charges_enabled'] && $account['payouts_enabled']) {
                // Rider verified
                Rider::where('stripe_account_id', $account['id'])
                    ->update(['is_stripe_verified' => true]);
            }
        }

        return response('ok', 200);
    }


    public function checkStripeStatus($accountId)
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $account = Account::retrieve($accountId);

        return response()->json([
            'charges_enabled' => $account->charges_enabled,
            'payouts_enabled' => $account->payouts_enabled,
        ]);
    }
}
