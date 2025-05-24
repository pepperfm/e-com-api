<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\ToggleBasketRequest;

class UserController extends Controller
{
    public function addToBasket(ToggleBasketRequest $request): \Illuminate\Http\JsonResponse
    {
        $basket = user()->getBasket();
        ray(
            $basket->getKey()
        );
        $basket->products()->syncWithoutDetaching($request->productId);

        return response()->json(['message' => 'Product added to basket']);
    }

    public function removeFromBasket(ToggleBasketRequest $request): \Illuminate\Http\JsonResponse
    {
        $basket = user()->getBasket();
        $basket->products()->detach($request->productId);

        return response()->json(['message' => 'Product removed from basket']);
    }
}
