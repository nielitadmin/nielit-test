<?php
/**
 * File name: PayPalSettings.php
 * Last modified: 21/06/21, 11:22 AM
 * Author: NearCraft - https://codecanyon.net/user/nearcraft
 * Copyright (c) 2021
 */

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class PayPalSettings extends Settings
{
    public string $client_id;
    public string $secret;
    public string $webhook_url;

    public static function group(): string
    {
        return 'paypal';
    }
}
