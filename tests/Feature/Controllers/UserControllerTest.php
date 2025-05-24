<?php

declare(strict_types=1);

use App\Models\Basket;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

use function Pest\Laravel\{actingAs, seed, postJson, deleteJson};

beforeEach(function () {
    seed(DatabaseSeeder::class);

    $this->user = User::first();

    actingAs($this->user, 'sanctum');
});

test('add to basket', function (int $count) {
    $products = Product::factory(25)->create();
    $user = user()->load('basket.products');

    expect($user->basket)->toBeNull();
    for ($i = 0; $i < $count; $i++) {
        $response = postJson(route('add-to-basket', ['product' => $products->random()->getKey()]));
        $response->assertOk();
        usleep(10000);
    }

    $basket = $user->refresh()->getBasket();

    expect($basket)->not()->toBeNull()
        ->and($basket->count())->toEqual(1)
        ->and($basket->products)->not()->toBeEmpty()
        ->and($basket->products->count())->toEqual($count);
})->with([
    // 'one product' => 1,
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
