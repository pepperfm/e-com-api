<?php

declare(strict_types=1);

use App\Http\Controllers\LoginController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('login', LoginController::class)->name('login.store');

Route::group([
    'middleware' => 'auth:sanctum',
], static function (): void {
    Route::post('add-to-basket', [UserController::class, 'addToBasket'])->name('add-to-basket');
    Route::post('create-order', [UserController::class, 'createOrder'])->name('create-order');
});
