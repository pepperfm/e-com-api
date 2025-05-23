<?php

declare(strict_types=1);

use App\Models\PaymentMethod;
use App\Models\Basket;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

use function Pest\Laravel\{actingAs, seed, getJson, postJson};

beforeEach(function () {
    seed(DatabaseSeeder::class);

    $this->user = User::first();

    actingAs($this->user, 'sanctum');
});

test('make payment', function () {
    Basket::factory()
        ->withUser($this->user)
        ->withProducts()
        ->create();
    $response = postJson(route('orders.store'), [
        'payment_method_id' => PaymentMethod::query()->inRandomOrder()->value('id'),
    ]);
    $response->assertOk();

    $this->user->tokens()->delete();

    $hash = $response->collect('payment_url')->first();

    $order = $this->user->orders()->first();
    expect($order->status)->toBe(\App\Enum\OrderStatusEnum::ReadyToPay);

    $response = getJson(route('orders.make-payment', ['hash' => $hash]));
    $response->assertOk();

    expect($order->refresh()->status)->toBe(\App\Enum\OrderStatusEnum::Paid);
});
