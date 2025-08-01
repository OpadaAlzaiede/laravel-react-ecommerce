<?php

use App\Enums\Roles\RoleEnum;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Config;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Response;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

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
        Route::post('/stripe/connect', [StripeController::class, 'connect'])->name('stripe.connect')
            ->middleware(['role:'.RoleEnum::VENDOR->value]);

        Route::post('become-vendor', [VendorController::class, 'store'])->name('vendor.store');

        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');

    });
});

Route::get('return', function () {
    $account = Auth::user()->retrieveStripeAccount();

    Auth::user()
        ->setStripeAccountStatus($account->details_submitted)
        ->save();

    return Route::has(Config::get('stripe_connect.routes.account.complete'))
        ? Response::redirectToRoute(Config::get('stripe_connect.routes.account.complete'))
        : Response::redirectTo('/');
})->name('stripe-connect.return');

Route::get('refresh', function () {
    return Response::redirectTo(Auth::user()->getStripeAccountLink());
})->name('stripe-connect.refresh');

require __DIR__.'/auth.php';
