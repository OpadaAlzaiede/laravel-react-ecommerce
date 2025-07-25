<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StripeController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

// Guest routes...
Route::get('/', [ProductController::class, 'index'])->name('dashboard');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');

Route::controller(CartController::class)->prefix('cart')->group(function() {

    Route::post('/checkout', 'checkout')->middleware(['auth', 'verified'])->name('cart.checkout');
    Route::get('/', 'index')->name('cart.index');
    Route::post('/{product}', 'store')->name('cart.store');
    Route::put('/{product}', 'update')->name('cart.update');
    Route::delete('/{product}', 'destroy')->name('cart.destroy');
});

Route::post('stripe/webhook', [StripeController::class, 'webhook'])->withoutMiddleware(ValidateCsrfToken::class)->name('stripe.webhook');

// Authenticated routes...
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware(['verified'])->group(function () {
        Route::get('/stripe/success', [StripeController::class, 'success'])->name('stripe.success');
        Route::get('/stripe/failure', [StripeController::class, 'failure'])->name('stripe.failure');
    });
});

require __DIR__.'/auth.php';
