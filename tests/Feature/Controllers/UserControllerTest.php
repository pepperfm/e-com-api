<?php

declare(strict_types=1);

use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

use function Pest\Laravel\{actingAs, seed, postJson};

beforeEach(function () {
    seed(DatabaseSeeder::class);
    actingAs(User::first(), 'sanctum');
});

test('add product to basket', function (int $count) {
    $products = Product::factory(25)->create();
    $user = user()->load('basket.products');

    expect($user->basket)->toBeNull();

    for ($i = 0; $i < $count; $i++) {
        $response = postJson(route('add-to-basket'), [
            'product_id' => $products->random()->getKey(),
        ]);
        $response->assertOk();
    }

    $user->refresh();

    expect($user->basket)->not()->toBeNull()
        ->and($user->basket->count())->toEqual(1)
        ->and($user->basket->products)->not()->toBeEmpty()
        ->and($user->basket->products->count())->toEqual($count);
})->with([
    'one product' => 1,
    'many products' => 5,
]);

test('cant add to basket', function () {
    Product::factory(5)->create();
    $response = postJson(route('add-to-basket'), [
        'product_id' => Product::query()->latest('id')->value('id') + 1,
    ]);
    $response->assertUnprocessable();
});

test('create order', function () {
    $products = Product::factory(25)->create();
    for ($i = 0; $i < 3; $i++) {
        $response = postJson(route('add-to-basket'), [
            'product_id' => $products->random()->getKey(),
        ]);
        $response->assertOk();
    }

    $user = user()->load('basket.products');

    expect($user->orders)->toBeEmpty();

    $response = postJson(route('create-order'), [
        'payment_method_id' => PaymentMethod::query()->inRandomOrder()->value('id'),
    ]);
    $response->assertOk();

    $user->refresh();

    expect($user->orders)->not()->toBeEmpty();

    /** @var \App\Models\Order $order */
    $order = $user->orders->first();

    expect($order->products)->not()->toBeEmpty()
        ->and($order->status)->toEqual(\App\Enum\OrderStatusEnum::ReadyToPay)
        ->and($user->basket)->toBeNull();
});

test('cant create order', function (callable $callable) {
    $data = $callable();
    $products = Product::factory(25)->create();
    if ($data['fill_basket']) {
        $response = postJson(route('add-to-basket'), [
            'product_id' => $products->random()->getKey(),
        ]);
        $response->assertOk();
    }

    $response = postJson(route('create-order'), [
        'payment_method_id' => $data['payment_method_id'],
    ]);
    $response->assertStatus($data['status']);
})->with([
    'payment method error' => static fn() => [
        'payment_method_id' => 123,
        'status' => 422,
        'fill_basket' => true,
    ],
    'empty basket error' => static fn() => [
        'payment_method_id' => PaymentMethod::query()->inRandomOrder()->value('id'),
        'status' => 404,
        'fill_basket' => false,
    ],
]);
