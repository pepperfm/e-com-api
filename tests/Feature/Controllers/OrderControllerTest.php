<?php

declare(strict_types=1);

use App\Enum\OrderStatusEnum;
use App\Jobs\CancelOldOrdersJob;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Basket;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\PaymentMethodSeeder;

use function Pest\Laravel\{actingAs, seed, travelTo, getJson, postJson};

beforeEach(function () {
    seed(PaymentMethodSeeder::class);

    $this->user = User::factory()->create();

    actingAs($this->user, 'sanctum');
});

test('index', function (callable $callable) {
    /**
     * @var array{
     *     direction: ?string,
     *     status: ?OrderStatusEnum ,
     *     expected_order_ids: array
     * } $data
     */
    $data = $callable();

    postJson(route('orders.store'), [
        'payment_method_id' => PaymentMethod::query()->inRandomOrder()->value('id'),
    ]);
    postJson(route('orders.store'), [
        'payment_method_id' => PaymentMethod::query()->inRandomOrder()->value('id'),
    ]);
    postJson(route('orders.store'), [
        'payment_method_id' => PaymentMethod::query()->inRandomOrder()->value('id'),
    ]);

    $response = getJson(route('orders.index', ['direction' => $data['direction'], 'status' => $data['status']]));
    $response->assertOk();

    $actualIds = $response->collect('entities')->pluck('id')->all();
    expect($actualIds)->toBe($data['expected_order_ids']);
})->with([
    'ascending, all statuses' => static fn() => [
        'direction' => 'asc',
        'status' => null,
        'expected_order_ids' => Order::latest()->pluck('id')->all(),
    ],
    'descending, all statuses' => static fn() => [
        'direction' => 'desc',
        'status' => null,
        'expected_order_ids' => Order::latest()->pluck('id')->all(),
    ],
    'default (latest by id), all statuses' => static fn() => [
        'direction' => null,
        'status' => null,
        'expected_order_ids' => Order::latest('id')->pluck('id')->all(),
    ],
    'filtered by status Paid' => static fn() => [
        'direction' => 'asc',
        'status' => OrderStatusEnum::Paid,
        'expected_order_ids' => Order::where('status', OrderStatusEnum::Paid)->latest()->pluck('id')->all(),
    ],
]);

test('show', function (callable $callable) {
    $data = $callable();

    Basket::factory()
        ->withUser($this->user)
        ->withProducts()
        ->create();

    postJson(route('orders.store'), [
        'payment_method_id' => PaymentMethod::query()->inRandomOrder()->value('id'),
    ])->assertOk();

    $order = $this->user->orders()->first();

    actingAs($data['user'], 'sanctum');

    $response = getJson(route('orders.show', ['order' => $order->getKey()]));
    $response->assertStatus($data['status']);
})->with([
    'my order' => static fn() => [
        'user' => user(),
        'status' => 200,
    ],
    'another order' => static fn() => [
        'user' => User::factory()->create(),
        'status' => 404,
    ],
]);

test('create order', function () {
    Product::factory(25)->create();
    Basket::factory()
        ->withUser($this->user)
        ->withProducts()
        ->create();

    $user = user()->load('basket.products');

    expect($user->orders)->toBeEmpty();

    $response = postJson(route('orders.store'), [
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

    $response = postJson(route('orders.store'), [
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

test('unpaid orders are cancelled after 2 minutes', function (int $minutes, OrderStatusEnum $status) {
    Basket::factory()
        ->withUser($this->user)
        ->withProducts()
        ->create();
    $response = postJson(route('orders.store'), [
        'payment_method_id' => PaymentMethod::query()->inRandomOrder()->value('id'),
    ]);
    $response->assertOk();

    travelTo(now()->addMinutes($minutes));

    CancelOldOrdersJob::dispatch();

    $order = $this->user->orders()->first();

    expect($order->refresh()->status)->toBe($status);
})->with([
    'new status' => [
        'minutes' => 3,
        'status' => OrderStatusEnum::Cancelled,
    ],
    'same status' => [
        'minutes' => 1,
        'status' => OrderStatusEnum::ReadyToPay,
    ],
]);
