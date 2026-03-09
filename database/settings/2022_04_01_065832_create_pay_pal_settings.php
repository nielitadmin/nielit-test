<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

class CreatePayPalSettings extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('payments.enable_paypal', false);
        $this->migrator->add('paypal.client_id', 'CLIENT_ID_HERE');
        $this->migrator->add('paypal.secret', 'SECRET_HERE');
        $this->migrator->add('paypal.webhook_url', 'webhooks/paypal');
    }
}
