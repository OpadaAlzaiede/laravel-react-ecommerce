<?php

use App\Enums\Products\ProductVariationTypeEnum;
use App\Models\CartItem;
use App\Models\User;

function createPhoneWithStorageOptions(): array
{
    $product = createProduct(['price' => 100]);

    $type = $product->variationTypes()->create([
        'name' => 'Storage',
        'type' => ProductVariationTypeEnum::RADIO->value,
    ]);

    $small = $type->options()->create(['name' => '128GB']);
    $large = $type->options()->create(['name' => '256GB']);

    $product->variations()->create(['variation_type_option_ids' => [$small->id], 'quantity' => 5, 'price' => 120]);
    $product->variations()->create(['variation_type_option_ids' => [$large->id], 'quantity' => 5, 'price' => 220]);

    return [$product, $type, $small, $large];
}

test('adding from a product card without options uses the first option and its price', function () {
    [$product, $type, $small] = createPhoneWithStorageOptions();

    $this->actingAs(User::factory()->create())
        ->post(route('cart.store', $product), ['option_ids' => [], 'quantity' => 1])
        ->assertRedirect();

    $cartItem = CartItem::sole();

    expect($cartItem->variation_type_option_ids)->toEqual([$type->id => $small->id])
        ->and((float) $cartItem->price)->toBe(120.0);
});

test('adding with a chosen option keeps that option and its price', function () {
    [$product, $type, , $large] = createPhoneWithStorageOptions();

    $this->actingAs(User::factory()->create())
        ->post(route('cart.store', $product), ['option_ids' => [$type->id => $large->id], 'quantity' => 1])
        ->assertRedirect();

    $cartItem = CartItem::sole();

    expect($cartItem->variation_type_option_ids)->toEqual([$type->id => $large->id])
        ->and((float) $cartItem->price)->toBe(220.0);
});

test('products without variations are added at their base price', function () {
    $product = createProduct(['price' => 75]);

    $this->actingAs(User::factory()->create())
        ->post(route('cart.store', $product), ['option_ids' => [], 'quantity' => 2])
        ->assertRedirect();

    $cartItem = CartItem::sole();

    expect($cartItem->variation_type_option_ids)->toBe([])
        ->and((float) $cartItem->price)->toBe(75.0)
        ->and($cartItem->quantity)->toBe(2);
});
