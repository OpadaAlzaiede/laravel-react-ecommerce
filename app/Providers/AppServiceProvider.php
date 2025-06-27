<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Product;
use App\Models\VariationTypeOption;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
