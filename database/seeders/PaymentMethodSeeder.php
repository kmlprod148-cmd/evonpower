<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentMethod;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paymentMethods = [
            [
                'name' => 'CMI',
                'slug' => 'cmi',
                'provider' => 'CMI',
                'is_active' => true,
                'config' => [
                    'base_url' => config('payment.cmi.base_url'),
                    'store_id' => config('payment.cmi.store_id'),
                    'store_key' => config('payment.cmi.store_key'),
                ],
                'logo_url' => '/images/payment-logos/cmi.png',
                'sort_order' => 1,
            ],
            [
                'name' => 'Stripe',
                'slug' => 'stripe',
                'provider' => 'Stripe',
                'is_active' => true,
                'config' => [
                    'public_key' => config('payment.stripe.public_key'),
                    'secret_key' => config('payment.stripe.secret_key'),
                    'webhook_secret' => config('payment.stripe.webhook_secret'),
                ],
                'logo_url' => '/images/payment-logos/stripe.png',
                'sort_order' => 2,
            ],
        ];

        foreach ($paymentMethods as $method) {
            PaymentMethod::updateOrCreate(
                ['slug' => $method['slug']],
                $method
            );
        }
    }
}
