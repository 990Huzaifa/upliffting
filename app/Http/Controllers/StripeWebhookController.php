<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Rider;
use Stripe\Event as StripeEvent;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Your Stripe webhook secret from dashboard
        $endpoint_secret = config('services.stripe.webhook.secret');

        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');
        $event = null;

        try {
            if ($endpoint_secret) {
                // Verify signature
                $event = Webhook::constructEvent(
                    $payload, $sig_header, $endpoint_secret
                );
            } else {
                // Skip verification (for testing only)
                $event = StripeEvent::constructFrom(json_decode($payload, true));
            }
        } catch (UnexpectedValueException $e) {
            // Invalid payload
            Log::error('Stripe webhook invalid payload: ' . $e->getMessage());
            return response('Invalid payload', 400);
        } catch (SignatureVerificationException $e) {
            // Invalid signature
            Log::error('Stripe webhook signature verification failed: ' . $e->getMessage());
            return response('Invalid signature', 400);
        }

        // Handle specific event types
        switch ($event->type) {
            case 'account.updated':
                $account = $event->data->object;

                $rider = Rider::where('stripe_account_id', $account->id)->first();

                if ($rider) {
                    $rider->update([
                        'charges_enabled' => $account->charges_enabled,
                        'payouts_enabled' => $account->payouts_enabled,
                    ]);

                    Log::info("Rider {$rider->id} Stripe account updated successfully.");
                } else {
                    Log::warning("No rider found for Stripe account: {$account->id}");
                }
                break;

            default:
                Log::info('Received unhandled Stripe event: ' . $event->type);
                break;
        }

        return response('Webhook handled', 200);
    }
}
