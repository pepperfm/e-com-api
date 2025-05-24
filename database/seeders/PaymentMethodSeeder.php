<?php

declare(strict_types=1);

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PaymentMethod;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        PaymentMethod::insert([
            ['name' => 'Bank Card', 'payment_path' => 'https://card.ru/payout'],
            ['name' => 'SBP', 'payment_path' => 'https://sbp.ru/payment'],
            ['name' => 'Stripe', 'payment_path' => 'https://stripe.com/checkout'],
        ]);
    }
}
