<?php

namespace App\Services;

use Exception;
use Stripe\Account;
use Stripe\AccountLink;
use Stripe\PaymentMethod;
use Stripe\SetupIntent;
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
        try{
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
        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
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

    public function steupIntent($customerId)
    {
        try {
            $setupIntent = SetupIntent::create([
                'customer' => $customerId,
            ]);

            return $setupIntent->client_secret;
        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function attachPaymentMethodToCustomer(string $customerId, string $paymentMethodId)
    {
        try {
            $customer = Customer::retrieve($customerId);
            $paymentMethod = PaymentMethod::retrieve($paymentMethodId);
            $paymentMethod->attach(['customer' => $customerId]);

            // Set the default payment method for the customer
            $customer->invoice_settings = [
                'default_payment_method' => $paymentMethodId,
            ];
            $customer->save();

            return [
                'success' => true,
                'message' => 'Payment method attached successfully',
            ];
        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function setDefaultPaymentMethod(string $customerId, string $paymentMethodId)
    {
        try {
            $customer = Customer::retrieve($customerId);
            $customer->invoice_settings = [
                'default_payment_method' => $paymentMethodId,
            ];
            $customer->save();

            return [
                'success' => true,
                'message' => 'Default payment method set successfully',
            ];
        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getCardDetails(string $paymentMethodId)
    {
        try {
            $paymentMethod = PaymentMethod::retrieve($paymentMethodId);
            return [
                'success' => true,
                'card' => $paymentMethod->card,
            ];
        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }



    // rider connected account methods
    
    public function  createConnectedAccount(string $email,string $type = 'express')
    {
        try {
            $account = Account::create([
                'type' => $type,
                'email' => $email,
            ]);

            return $account->id;
        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function createOnboardingLink(string $accountId)
    {
        try {
            $link = AccountLink::create([
                'account' => $accountId,
                'refresh_url' => config('app.url').'/api/stripe/onboarding/refresh',
                'return_url' => config('app.url').'/api/stripe/onboarding/success',
                'type' => 'account_onboarding',
            ]);

            return $link->url;
        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function retrieveAccount(string $accountId)
    {
        try {
            $account = Account::retrieve($accountId);
            if ($account->charges_enabled && $account->payouts_enabled) {
                return [
                    'success' => true,
                    'is_verified'=> true
                ];
            } else {
                return [
                    'success' => true,
                    'is_verified'=> false
                ];
            }
        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
