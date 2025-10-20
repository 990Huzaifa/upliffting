<?php

namespace App\Services;

use Exception;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;

class StripeService
{
    public function __construct()
    {
        // Stripe Secret Key from .env
        Stripe::setApiKey(config('services.stripe.secret'));
    }



    public function createPaymentIntent($customerId, $paymentMethodId, $amount, $driverAccountId)
    {
        // set commission of 25%
        $amountInCents = $amount * 100;

        // Calculate 25% commission
        $commission = intval($amountInCents * 0.25);

        $paymentIntent = PaymentIntent::create([
            'amount' => $amountInCents, // in cents
            'currency' => 'usd',
            'customer' => $customerId,
            'payment_method' => $paymentMethodId,
            'confirm' => true,
            'transfer_data' => [
                'destination' => $driverAccountId, // driver ka connected account ID
            ],
            'application_fee_amount' => $commission, // tumhara commission (in cents)
        ]);

        return $paymentIntent;
    }

    public function createCustomer(string $name, string $email, string $phone = null)
    {
        try {
            $customer = Customer::create([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
            ]);

            return $customer->id;
        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
