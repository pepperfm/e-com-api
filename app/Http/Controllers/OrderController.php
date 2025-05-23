<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\CreateOrderRequest;
use App\Enum\OrderStatusEnum;
use App\Models\Order;

class OrderController extends Controller
{
    public function store(CreateOrderRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            return db()->transaction(function () use ($request) {
                $basket = user()->getBasket();
                if ($basket->products->isEmpty()) {
                    return response()->json(['message' => 'Empty basket'], 404);
                }

                $order = user()->orders()->create([
                    'payment_method_id' => $request->paymentMethodId,
                ]);

                // шаблон для pivot-данных
                // $attachData = [];
                // foreach ($basket->products as $product) {
                //     $attachData[$product->id] = [
                //         'quantity' => $product->pivot->quantity ?? 1,
                //         'price' => $product->pivot->price ?? $product->price,
                //     ];
                // }
                // $order->products()->attach($attachData);

                $order->products()->attach($basket->products->pluck('id')->all());

                $basket->products()->detach();
                $basket->delete();

                return response()->json([
                    'payment_url' => $order->generatePaymentUrl(),
                    'message' => 'Order created',
                ]);
            });
        } catch (\Throwable $e) {
            logger()->error($e->getMessage(), $e->getTrace());

            return response()->json(['message' => 'Omg!'], 500);
        }
    }

    public function makePayment(Order $order): \Illuminate\Http\JsonResponse
    {
        $order->update(['status' => OrderStatusEnum::Paid]);

        return response()->json(['message' => 'Payment made successfully']);
    }
}
