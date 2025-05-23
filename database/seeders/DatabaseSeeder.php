<?php

declare(strict_types=1);

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        Product::factory(100)->create();

        PaymentMethod::insert([
            ['name' => 'Bank Card', 'payment_path' => 'https://card.ru/payout'],
            ['name' => 'SBP', 'payment_path' => 'https://sbp.ru/payment'],
            ['name' => 'Stripe', 'payment_path' => 'https://stripe.com/checkout'],
        ]);
    }
}
