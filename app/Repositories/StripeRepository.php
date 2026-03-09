<?php
/**
 * File name: StripeRepository.php
 * Last modified: 01/04/22, 3:50 PM
 * Author: NearCraft - https://codecanyon.net/user/nearcraft
 * Copyright (c) 2022
 */

namespace App\Repositories;

use App\Settings\PaymentSettings;
use App\Settings\StripeSettings;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;

class StripeRepository
{
    /**
     * @var StripeSettings
     */
    private StripeSettings $settings;

    public function __construct(StripeSettings $settings)
    {
        $this->settings = $settings;
        Stripe::setApiKey($settings->secret_key);
    }

    /**
     * @param $paymentId
     * @param $name
     * @param $amount
     * @return Session
     * @throws ApiErrorException
     */
    public function createSession($paymentId, $name, $amount)
    {
        return Session::create([
            'customer_email' => auth()->user()->email,
            'client_reference_id' => $paymentId,
            'line_items' => [[
                'price_data' => [
                    'currency' => app(PaymentSettings::class)->default_currency,
                    'product_data' => [
                        'name' => $name,
                    ],
                    'unit_amount' => $amount,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => route('payment_success'),
            'cancel_url' => route('payment_cancelled'),
        ]);
    }
}
