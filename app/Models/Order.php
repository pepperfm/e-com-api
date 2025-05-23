<?php

declare(strict_types=1);

namespace App\Models;

use App\Enum\OrderStatusEnum;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $casts = [
        'total_sum' => 'decimal:2',
        'status' => OrderStatusEnum::class,
    ];

    protected $attributes = [
        'status' => OrderStatusEnum::ReadyToPay,
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function products(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphToMany(Product::class, 'productable');
    }

    public function paymentMethod(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}
