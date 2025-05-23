<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    public $timestamps = false;

    public function orders(): \Illuminate\Database\Eloquent\Relations\HasMany|PaymentMethod
    {
        return $this->hasMany(Order::class);
    }
}
