<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Data\CreateOrderRequest;
use App\Enum\OrderStatusEnum;
use App\Models\Order;

class OrderController extends Controller
{
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'direction' => ['sometimes', 'nullable', 'string', 'in:asc,desc'],
            'status' => ['sometimes', 'nullable', Rule::enum(OrderStatusEnum::class)],
        ]);

        $orders = $request->user()->orders()
            ->when(
                $request->query('direction'),
                static fn(Builder $query, string $direction) => $query->orderBy('created_at', $direction),
                static fn(Builder $query) => $query->latest('id')
            )
            ->when(
                $request->query('status'),
                static fn(Builder $query, string $status) => $query->where('status', $status),
            )
            ->get();

        return response()->json(['entities' => $orders]);
    }

    public function show(int $id): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'entity' => user()->orders()->findOrFail($id),
        ]);
    }

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
