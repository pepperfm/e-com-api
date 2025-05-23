<?php

declare(strict_types=1);

namespace App\Enum;

enum OrderStatusEnum: int
{
    case ReadyToPay = 1;
    case Paid = 2;
    case Cancelled = 3;

    public function getLabel(): string
    {
        return match ($this) {
            self::ReadyToPay => 'На оплату',
            self::Paid => 'Оплачен',
            self::Cancelled => 'Отменён',
        };
    }
}
