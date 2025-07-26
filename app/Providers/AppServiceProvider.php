<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Product;
use Stripe\StripeClient;
use App\Services\CartService;
use App\Models\VariationTypeOption;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Services\Interfaces\StripeConnect as StripeConnectInterface;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CartService::class, function() {
            return new CartService();
        });

        $this->app->singleton(StripeConnectInterface::class, function () {
            return new StripeClient(Config::get('stripe_connect.stripe.secret'));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::morphMap([
            'user' => User::class,
            'product' => Product::class,
            'variationTypeOption' => VariationTypeOption::class,
        ]);

        Vite::prefetch(concurrency: 3);
    }
}
