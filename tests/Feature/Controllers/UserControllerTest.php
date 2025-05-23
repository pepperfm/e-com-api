<?php

declare(strict_types=1);

use App\Models\Basket;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

use function Pest\Laravel\{actingAs, seed, postJson, deleteJson};

beforeEach(function () {
    seed(DatabaseSeeder::class);

    $this->user = User::first();

    actingAs($this->user, 'sanctum');
});

test('add product to basket', function (int $count) {
    Product::factory(25)->create();
    $user = user()->load('basket.products');

    expect($user->basket)->toBeNull();

    $basket = Basket::factory()
        ->withUser($user)
        ->withProducts($count)
        ->create();

    expect($basket)->not()->toBeNull()
        ->and($basket->count())->toEqual(1)
        ->and($basket->products)->not()->toBeEmpty()
        ->and($basket->products->count())->toEqual($count);
})->with([
    'one product' => 1,
    'many products' => 5,
]);

test('cant add to basket', function () {
    Product::factory(5)->create();
    $response = postJson(route('add-to-basket', [
        'product' => Product::query()->latest('id')->value('id') + 1,
    ]));
    $response->assertUnprocessable();
});

test('remove product from basket', function () {
    $products = Product::factory(25)->create();
    $user = user()->load('basket.products');

    expect($user->basket)->toBeNull();

    $product = $products->random();

    $basket = Basket::factory()
        ->withUser($user)
        ->withProducts(1, $product)
        ->create();

    $user->refresh();

    $response = deleteJson(route('remove-from-basket', ['product' => $product->getKey()]));
    $response->assertOk();

    $user->refresh();

    expect($basket)->not()->toBeNull()
        ->and($basket->count())->toEqual(1)
        ->and($basket->products)->toBeEmpty();
});

test('create order', function () {
    Product::factory(25)->create();
    Basket::factory()
        ->withUser($this->user)
        ->withProducts()
        ->create();

    $user = user()->load('basket.products');

    expect($user->orders)->toBeEmpty();

    $response = postJson(route('create-order'), [
        'payment_method_id' => PaymentMethod::query()->inRandomOrder()->value('id'),
    ]);
    $response->assertOk();

    $user->refresh();

    expect($user->orders)->not()->toBeEmpty();

    /** @var \App\Models\Order $order */
    $order = $user->orders->first()->refresh();

    expect($order->products)->not()->toBeEmpty()
        ->and($order->status)->toEqual(\App\Enum\OrderStatusEnum::ReadyToPay)
        ->and($user->basket)->toBeNull();
});

test('cant create order', function (callable $callable) {
    $data = $callable();
    $products = Product::factory(25)->create();
    if ($data['fill_basket']) {
        Basket::factory()
            ->withUser($this->user)
            ->withProducts(product: $products->random())
            ->create();
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
