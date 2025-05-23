<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Basket;
use App\Models\User;

class BasketFactory extends Factory
{
    protected $model = Basket::class;

    public function definition(): array
    {
        return [];
    }

    public function withUser(?User $user = null): static
    {
        return $this->state(fn() => [
            'user_id' => $user?->getKey() ?? User::factory(),
        ]);
    }

    public function withProducts(int $count = 3, ?Product $product = null): static
    {
        return $this->afterCreating(function (Basket $basket) use ($count, $product) {
            if (!$product) {
                $attachData = Product::factory($count)->create();
            } else {
                $attachData = $product->getKey();
            }

            // $attachData = [];
            // foreach ($products as $product) {
            //     $attachData[$product->id] = [
            //         'quantity' => fake()->numberBetween(1, 5),
            //         'price' => $product->price,
            //     ];
            // }

            $basket->products()->attach($attachData);
        });
    }
}
