<?php

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    config(['database.connections.other_customer' => config('database.connections.'.config('database.default'))]);
});

afterEach(function () {
    DB::purge('other_customer');
});

test('a reservation that loses the race reports the current stock, not a stale snapshot', function () {
    $product = createProduct(['title' => 'Galaxy Phone', 'quantity' => 10]);

    DB::beginTransaction();

    $productInCheckout = Product::with('variations')->findOrFail($product->id);

    DB::connection('other_customer')->table('products')->where('id', $product->id)->update(['quantity' => 0]);

    try {
        expect(fn () => app(StockService::class)->reserve($productInCheckout, [], 10))
            ->toThrow(InsufficientStockException::class, 'Galaxy Phone is out of stock.');
    } finally {
        DB::rollBack();
    }

    expect($product->fresh()->quantity)->toBe(0);
});

test('a reservation that loses the race to a partial purchase reports what is really left', function () {
    $product = createProduct(['title' => 'Galaxy Phone', 'quantity' => 10]);

    DB::beginTransaction();

    $productInCheckout = Product::with('variations')->findOrFail($product->id);

    DB::connection('other_customer')->table('products')->where('id', $product->id)->update(['quantity' => 4]);

    try {
        expect(fn () => app(StockService::class)->reserve($productInCheckout, [], 10))
            ->toThrow(InsufficientStockException::class, 'Only 4 of Galaxy Phone left in stock.');
    } finally {
        DB::rollBack();
    }
});
