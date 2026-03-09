<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

class CreateStripeSettings extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('payments.enable_stripe', false);
        $this->migrator->add('stripe.api_key', 'STRIPE_API_KEY_HERE');
        $this->migrator->add('stripe.secret_key', 'STRIPE_SECRET_KEY_HERE');
        $this->migrator->add('stripe.webhook_url', 'webhooks/stripe');
        $this->migrator->add('stripe.webhook_secret', 'STRIPE_WEBHOOK_SECRET_HERE');
    }
}
