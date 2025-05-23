<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Enum\OrderStatusEnum;
use App\Models\Order;

class CancelOldOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Order::query()
            ->select(['id', 'status', 'created_at'])
            ->where('status', OrderStatusEnum::ReadyToPay)
            ->where('created_at', '<=', now()->subMinutes(2))
            ->update(['status' => OrderStatusEnum::Cancelled]);
    }
}
