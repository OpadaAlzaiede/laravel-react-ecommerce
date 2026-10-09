<?php

use App\Enums\Roles\RoleEnum;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StripeConnectController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\UserVendorController;
use App\Http\Controllers\VendorController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

// Guest routes...
Route::get('/', [HomeController::class, 'home'])->name('dashboard');
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');

Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('categories/{category}', [CategoryController::class, 'show'])->name('categories.show');

Route::get('vendors', [UserVendorController::class, 'index'])->name('vendors.index');
Route::get('vendors/{vendor}', [UserVendorController::class, 'show'])->name('vendors.show');

Route::get('about', [HomeController::class, 'about'])->name('about');
Route::get('contact', [ContactController::class, 'show'])->name('contact');
Route::post('contact', [ContactController::class, 'send'])->middleware('throttle:5,1')->name('contact.send');

Route::controller(CartController::class)->prefix('cart')->group(function () {

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

    Route::get('return', [StripeConnectController::class, 'returnFromOnboarding'])->name('stripe-connect.return');
    Route::get('refresh', [StripeConnectController::class, 'refreshOnboarding'])->name('stripe-connect.refresh');

    Route::middleware(['verified'])->group(function () {
        Route::get('/stripe/success', [StripeController::class, 'success'])->name('stripe.success');
        Route::get('/stripe/failure', [StripeController::class, 'failure'])->name('stripe.failure');
        Route::post('/stripe/connect', [StripeConnectController::class, 'connect'])->name('stripe.connect')
            ->middleware(['role:'.RoleEnum::VENDOR->value]);

        Route::post('become-vendor', [VendorController::class, 'store'])->name('vendor.store');

        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->can('view', 'order')->name('orders.show');

    });
});

require __DIR__.'/auth.php';
