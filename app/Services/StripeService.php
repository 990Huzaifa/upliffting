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
    
    public function  createConnectedAccount(string $email, string $country,string $type = 'express')
    {
        try {
            $account = Account::create([
                'type' => $type, // Express account, Custom UI ke liye best
                'country' => $country,
                'email' => $email, // Optional, agar aap pehle se email dena chahte hain
                'capabilities' => [ // Zaroori capabilities set karein
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                ],
                'settings' => [
                    'payouts' => [
                        'schedule' => ['interval' => 'manual'], // Manual payouts rakhein shuruat mein
                    ],
                ]
            ]);

            return $account->id;
        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'error_message' => $e->getMessage(), // Key ka naam badal diya
                'http_status' => $e->getHttpStatus(), // HTTP status bhi de sakte hain
            ];
        }
    }

    public function UpdateSSN(string $accountId, string $ssn)
    {
        try {
            $account = Account::update(
                $accountId,
                [
                    'individual' => [
                        // Note: Depending on the country and Stripe's requirements,
                        // this field might be 'id_number' or 'ssn_last_4'.
                        // For a full custom flow, using 'id_number' is common for the full number.
                        'id_number' => $ssn, 
                    ],
                ]
            );
            return [
                'success' => true,
                'account_id' => $account->id,
                // Stripe ki taraf se kya required hai, woh check karne ke liye
                'verification_status' => $account->requirements->currently_due ?? [],
                'details_submitted' => $account->details_submitted,
            ];
        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'error_message' => $e->getMessage(),
                'http_status' => $e->getHttpStatus(),
            ];
        }
    }

    public function addBankAccount(string $accountId, string $token): array
    {
        try {
            // External Account create karein (token ke zariye)
            $externalAccount = Account::createExternalAccount(
                $accountId,
                [
                    'external_account' => $token, // Token-based approach
                ]
            );

            // Success response
            return [
                'success' => true,
                'external_account_id' => $externalAccount->id, // Bank account ka ID
                'bank_name' => $externalAccount->bank_name,
                'last_4' => $externalAccount->last4,
                'status' => $externalAccount->status, // Verification status
            ];

        } catch (ApiErrorException $e) {
            // Error handling
            return [
                'success' => false,
                'error_message' => $e->getMessage(),
                'http_status' => $e->getHttpStatus(),
            ];
        }
    }

    public function tosAcceptance(string $accountId, string $ip)
    {
        try {
            // Get the current UNIX timestamp
            $timestamp = time();
            $account = Account::update(
                $accountId,
                [
                    'tos_acceptance' => [
                        'date' => $timestamp,
                        'ip' => $ip,
                    ],
                ]
            );

            return [
                'success' => true,
                'message' => 'TOS accepted successfully',
            ];
        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    // public function createOnboardingLink(string $accountId, $id)
    // {
    //     try {
    //         $link = AccountLink::create([
    //             'account' => $accountId,
    //             'refresh_url' => 'https://devcatsoftwares.com/refresh.php/'.$id,
    //             'return_url' => 'https://devcatsoftwares.com/redirector.php?link=' . urlencode('https://devcatsoftwares.com/success.php/'.$id),
    //             'type' => 'account_onboarding',
    //         ]);

    //         return $link->url;
    //     } catch (ApiErrorException $e) {
    //         return [
    //             'success' => false,
    //             'error' => $e->getMessage(),
    //         ];
    //     }
    // }

    // public function retrieveAccount(string $accountId)
    // {
    //     try {
    //         $account = Account::retrieve($accountId);
    //         if ($account->charges_enabled && $account->payouts_enabled) {
    //             return [
    //                 'success' => true,
    //                 'is_verified'=> true
    //             ];
    //         } else {
    //             return [
    //                 'success' => true,
    //                 'is_verified'=> false
    //             ];
    //         }
    //     } catch (ApiErrorException $e) {
    //         return [
    //             'success' => false,
    //             'error' => $e->getMessage(),
    //         ];
    //     }
    // }
}
