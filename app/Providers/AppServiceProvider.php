<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Product;
use Stripe\StripeClient;
use App\Services\CartService;
use App\Contracts\Payments\CheckoutGateway;
use App\Services\Payments\StripeCheckoutGateway;
use App\Contracts\Payments\WebhookGateway;
use App\Services\Payments\StripeWebhookGateway;
use App\Models\VariationTypeOption;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Services\Interfaces\StripeConnect as StripeConnectInterface;
use Illuminate\Support\Facades\Schedule;

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
            return new StripeClient(['api_key' => Config::get('stripe_connect.stripe.secret')]);
        });

        $this->app->singleton(CheckoutGateway::class, function () {
            return new StripeCheckoutGateway(new StripeClient(['api_key' => Config::get('app.stripe_secret_key')]));
        });

        $this->app->singleton(WebhookGateway::class, function () {
            return new StripeWebhookGateway(
                new StripeClient(['api_key' => Config::get('app.stripe_secret_key')]),
                Config::get('app.stripe_webhook_secret'),
            );
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

        Schedule::command('pay:vendors')->monthlyOn(1, '00:00')->withoutOverlapping();

        Vite::prefetch(concurrency: 3);
    }
}
