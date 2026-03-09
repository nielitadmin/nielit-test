<?php
/**
 * File name: StripeSettings.php
 * Last modified: 21/06/21, 11:22 AM
 * Author: NearCraft - https://codecanyon.net/user/nearcraft
 * Copyright (c) 2021
 */

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class StripeSettings extends Settings
{
    public string $api_key;
    public string $secret_key;
    public string $webhook_url;
    public string $webhook_secret;

    public static function group(): string
    {
        return 'stripe';
    }
}
