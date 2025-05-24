<?php

declare(strict_types=1);

use App\Http\Controllers\LoginController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('login', LoginController::class)->name('login.store');

Route::group([
    'middleware' => 'auth:sanctum',
], static function (): void {
    Route::post('add-to-basket/{product}', [UserController::class, 'addToBasket'])->name('add-to-basket');
    Route::delete('remove-from-basket/{product}', [UserController::class, 'removeFromBasket'])->name('remove-from-basket');

    Route::get('orders/{hash}/make-payment', [OrderController::class, 'makePayment'])->name('orders.make-payment');
    Route::apiResource('orders', OrderController::class)->only(['index', 'show', 'store']);

    Route::apiResource('products', ProductController::class)->only(['index', 'show']);
});
