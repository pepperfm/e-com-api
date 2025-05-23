<?php

declare(strict_types=1);

use App\Enum\OrderStatusEnum;
use App\Models\Order;

Schedule::call(static function (): void {
    Order::query()
        ->select(['id', 'status', 'created_at'])
        ->where('status', OrderStatusEnum::ReadyToPay)
        ->where('created_at', '<=', now()->subMinutes(2))
        ->update(['status' => OrderStatusEnum::Cancelled]);
})->everyMinute();
