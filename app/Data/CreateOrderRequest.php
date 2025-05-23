<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class CreateOrderRequest extends Data
{
    public function __construct(
        #[Exists(\App\Models\PaymentMethod::class, 'id')]
        public int $paymentMethodId,
    ) {
    }
}
