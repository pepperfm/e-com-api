<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\User;

use function Pest\Laravel\{actingAs, getJson};

beforeEach(function () {
    $this->user = User::factory()->create();

    actingAs($this->user, 'sanctum');
});

test('index', function (?string $direction, array $expectedPrices) {
    Product::factory()->create([
        'price' => 10.50,
        'created_at' => now()->subDays(2),
    ]);
    Product::factory()->create([
        'price' => 5.25,
        'created_at' => now()->subDay(),
    ]);
    Product::factory()->create([
        'price' => 20.00,
        'created_at' => now(),
    ]);

    $query = $direction ? ['direction' => $direction] : [];
    $response = getJson(route('products.index', $query));
    $response->assertOk();

    $actualPrices = collect($response->json())
        ->pluck('price')
        ->map(static fn(string $price) => (float) $price)
        ->all();

    expect($actualPrices)->toBe($expectedPrices);
})->with([
    'ascending' => [
        'direction' => 'asc',
        'expectedPrices' => [5.25, 10.50, 20.00],
    ],
    'descending' => [
        'direction' => 'desc',
        'expectedPrices' => [20.00, 10.50, 5.25],
    ],
    'default (by latest)' => [
        'direction' => null,
        'expectedPrices' => [20.00, 5.25, 10.50],
    ],
]);
