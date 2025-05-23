<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\AddToBasketRequest;
use App\Data\CreateOrderRequest;

class UserController extends Controller
{
    public function addToBasket(AddToBasketRequest $request): \Illuminate\Http\JsonResponse
    {
        $basket = user()->getBasket();
        $basket->products()->syncWithoutDetaching($request->productId);

        return response()->json(['message' => 'Product added to basket']);
    }

    public function createOrder(CreateOrderRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            db()->beginTransaction();

            $basket = user()->getBasket();
            if ($basket->products->isEmpty()) {
                return response()->json(['message' => 'Empty basket'], status: 404);
            }
            $order = user()->orders()->create(['payment_method_id' => $request->paymentMethodId]);
            $order->products()->attach($basket->products);

            // шаблон для pivot-данных
            // $attachData = [];
            // foreach ($basket->products as $product) {
            //     $attachData[$product->id] = [
            //         'quantity' => $product->pivot->quantity ?? 1,
            //         'price' => $product->pivot->price ?? $product->price,
            //     ];
            // }
            // $order->products()->attach($attachData);

            $basket->products()->detach();
            $basket->delete();

            db()->commit();
        } catch (\Throwable $e) {
            db()->rollback();
            logger($e->getMessage(), $e->getTrace());

            return response()->json(['message' => 'Omg!'], status: 500);
        }

        return response()->json();
    }
}
