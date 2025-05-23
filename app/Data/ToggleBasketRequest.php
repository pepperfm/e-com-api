<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\FromRouteParameter;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class ToggleBasketRequest extends Data
{
    public function __construct(
        #[FromRouteParameter('product'), Exists(\App\Models\Product::class, 'id')]
        public string $productId,
    ) {
    }
}
