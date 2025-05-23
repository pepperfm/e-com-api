<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enum\OrderStatusEnum;
use App\Models\Order;

class OrderController extends Controller
{
    public function makePayment(Order $order): \Illuminate\Http\JsonResponse
    {
        $order->update(['status' => OrderStatusEnum::Paid]);

        return response()->json(['message' => 'Payment made successfully']);
    }
}
