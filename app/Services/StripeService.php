<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Stripe\Account;
use Stripe\AccountLink;
use Stripe\PaymentMethod;
use Stripe\SetupIntent;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Customer;
use Stripe\File;
use Stripe\Exception\ApiErrorException;

class StripeService
{
    public function __construct()
    {
        // Stripe Secret Key from .env
        Stripe::setApiKey(config('services.stripe.secret'));
    }


    /**
     * Processes a customer payment, applies a 25% platform commission, and transfers
     * the remainder plus any tip to the connected rider account.
     *
     * @param string $paymentMethodId The customer's payment method ID (e.g., 'pm_card_visa').
     * @param int $amount The total amount to charge the customer, in the smallest currency unit (e.g., cents/pennies).
     * @param string $riderStripeAccountId The Stripe Connect Account ID of the rider (e.g., 'acct_XXXXXXXXXXXXXX').
     * @param string $currency The currency code (e.g., 'usd', 'gbp').
     * @param int $tipAmount Optional. The tip amount to send entirely to the rider, in smallest currency unit. Defaults to 0.
     * @return \Stripe\PaymentIntent The successfully processed PaymentIntent object.
     * @throws ApiErrorException If the payment or transfer fails.
     */
    public function chargeAndTransfer(string $paymentMethodId,int $amount,string $riderStripeAccountId,string $currency,int $tipAmount = 0): PaymentIntent
    {
        $amountInCents = $amount * 100;
        $platformCommissionPercentage = 0.25;

        $platformCommissionAmount = (int) round($amountInCents * $platformCommissionPercentage);

        // The remaining amount after commission (75% of base charge)
        $amountToRiderBeforeTip = $amountInCents - $platformCommissionAmount;

        // The total amount to transfer to the rider (75% base + 100% tip)
        $totalAmountToRider = $amountToRiderBeforeTip + $tipAmount;

        if ($amountInCents <= 0 || $totalAmountToRider < 0) {
            throw new \InvalidArgumentException('Invalid charge or transfer amount.');
        }
        if ($totalAmountToRider > $amountInCents) {
             throw new \InvalidArgumentException('Transfer amount cannot exceed total charge amount.');
        }
        try{
            $paymentIntent = PaymentIntent::create([
                'amount' => $amountInCents,
                'currency' => $currency,
                'payment_method' => $paymentMethodId,
                'confirm' => true, // Confirm the payment immediately
                'off_session' => true, // Use off_session if charging an existing card
                
                // The Platform Fee: This amount is deducted from the total charge and goes to your platform's balance.
                'application_fee_amount' => $platformCommissionAmount,
                
                // The Transfer: This handles transferring the remaining funds (75% + Tip) to the rider.
                'transfer_data' => [
                    'destination' => $riderStripeAccountId,
                    'amount' => $totalAmountToRider,
                ],
                
                // Optional: Add metadata for your records
                'metadata' => [
                    'rider_account_id' => $riderStripeAccountId,
                    'platform_commission' => $platformCommissionAmount,
                    'base_fare_transfer' => $amountToRiderBeforeTip,
                    'tip_amount' => $tipAmount,
                ],
            ]);

            if ($paymentIntent->status !== 'succeeded') {
                throw new Exception("PaymentIntent status was '{$paymentIntent->status}' instead of 'succeeded'.");
            }

            return $paymentIntent;
        } catch (ApiErrorException $e) {
            // Log the error for debugging purposes
            Log::error('Stripe Charge and Transfer Failed: ' . $e->getMessage(), [
                'code' => $e->getStripeCode(),
                'intent_id' => $paymentIntent->id ?? 'N/A',
                'rider_id' => $riderStripeAccountId,
            ]);
            // Re-throw the exception so the calling controller/job can handle the response
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
    
    public function  createConnectedAccount(string $email, string $country,string $type = 'custom')
    {
        try {
            $account = Account::create([
                'type' => $type, // Express account, Custom UI ke liye best
                'country' => $country,
                'tos_acceptance' => ['service_agreement' => 'recipient'],
                'email' => $email, // Optional, agar aap pehle se email dena chahte hain
                'capabilities' => [ // Zaroori capabilities set karein
                    'transfers' => ['requested' => true],
                ],
                'settings' => [
                    'payouts' => [
                        'schedule' => ['interval' => 'manual'], // Manual payouts rakhein shuruat mein
                    ],
                ]
            ]);

            return [
                'success' => true,
                'account_id' => $account->id,
            ];
        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'error_message' => $e->getMessage(), // Key ka naam badal diya
                'http_status' => $e->getHttpStatus(), // HTTP status bhi de sakte hain
            ];
        }
    }

    public function UpdateSSN(string $accountId, $firstName, $lastName, string $ssn, string $phone): array
    {
        try {
            $account = Account::update(
                $accountId,
                [
                    'business_type' => "individual",
                    'phone' => $phone,
                    'individual' => [
                        // Note: Depending on the country and Stripe's requirements,
                        // this field might be 'id_number' or 'ssn_last_4'.
                        // For a full custom flow, using 'id_number' is common for the full number.
                        'id_number' => $ssn,
                        'first_name' => $firstName,  // <--- Naam Yahaan Store Hoga
                        'last_name' => $lastName, 
                    ],
                    'business_profile' => [
                        "product_description" => "Ride sharing services",
                        'mcc' => '5734', // Example MCC (Merchant Category Code). Ye industry ke hisaab se badlega.
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

    public function UpdateInfo(
        string $accountId, $firstName, $lastName, string $ssn, string $phone,
        int $dobDay,           // Date of Birth: Day
        int $dobMonth,         // Date of Birth: Month
        int $dobYear,          // Date of Birth: Year
        array $addressData
        ): array
    {
        try {
            $account = Account::update(
                $accountId,
                [
                    'business_type' => "individual",
                    'phone' => $phone,
                    'individual' => [
                        // Note: Depending on the country and Stripe's requirements,
                        // this field might be 'id_number' or 'ssn_last_4'.
                        // For a full custom flow, using 'id_number' is common for the full number.
                        'id_number' => $ssn,
                        'first_name' => $firstName,  // <--- Naam Yahaan Store Hoga
                        'last_name' => $lastName, 
                        'dob' => [
                            'day' => $dobDay,
                            'month' => $dobMonth,
                            'year' => $dobYear,
                        ],
                        // 2. Address
                        'address' => $addressData,
                    ],
                    'business_profile' => [
                        "product_description" => "Ride sharing services",
                        'mcc' => '5734', // Example MCC (Merchant Category Code). Ye industry ke hisaab se badlega.
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
                'account_holder_name' => $externalAccount->account_holder_name,
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

    public function uploadVerificationDocument(string $accountId, string $filePath): array
    {
        try {
            // Step A: Upload the file to Stripe
            $uploadedFile = File::create([
                'purpose' => 'identity_document',
                'file' => fopen($filePath, 'r'), // Open the file resource
            ]);

            // Step B: Attach the uploaded file to the account
            $account = Account::update(
                $accountId,
                [
                    'verification' => [
                        'document' => $uploadedFile->id,
                    ],
                ]
            );
            return [
                'success' => true,
                'file_id' => $uploadedFile->id,
                'verification_status' => $account->requirements->currently_due ?? [],
            ];
        } catch (ApiErrorException $e) {
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
