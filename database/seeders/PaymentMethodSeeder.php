<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paymentMethods = [
            [
                'name' => 'cod',
                'display_name' => 'Cash on Delivery',
                'description' => 'Pay when your order is delivered',
                'is_active' => true,
                'config' => null,
            ],
            [
                'name' => 'stripe',
                'display_name' => 'Credit/Debit Card',
                'description' => 'Pay securely with your credit or debit card',
                'is_active' => true,
                'config' => [
                    'public_key' => env('STRIPE_PUBLIC_KEY'),
                    'secret_key' => env('STRIPE_SECRET_KEY'),
                ],
            ],
            [
                'name' => 'razorpay',
                'display_name' => 'Razorpay',
                'description' => 'Pay with Razorpay payment gateway',
                'is_active' => true,
                'config' => [
                    'key_id' => env('RAZORPAY_KEY_ID'),
                    'key_secret' => env('RAZORPAY_KEY_SECRET'),
                ],
            ],
        ];

        foreach ($paymentMethods as $method) {
            PaymentMethod::updateOrCreate(
                ['name' => $method['name']],
                $method
            );
        }
    }
}
