<?php

use App\Enums\Products\ProductVariationTypeEnum;
use App\Models\Product;
use Illuminate\Testing\TestResponse;

function createPhoneWithStorageAndColor(): array
{
    $product = createProduct(['price' => 100]);

    $storage = $product->variationTypes()->create(['name' => 'Storage', 'type' => ProductVariationTypeEnum::RADIO->value]);
    $small = $storage->options()->create(['name' => '128GB']);
    $large = $storage->options()->create(['name' => '256GB']);

    $color = $product->variationTypes()->create(['name' => 'Color', 'type' => ProductVariationTypeEnum::RADIO->value]);
    $black = $color->options()->create(['name' => 'Black']);

    $product->variations()->create(['variation_type_option_ids' => [$small->id, $black->id], 'quantity' => 5, 'price' => 120]);
    $product->variations()->create(['variation_type_option_ids' => [$large->id, $black->id], 'quantity' => 5, 'price' => 220]);

    return [$product, $storage, $color, $small, $large, $black];
}

function addToCart(Product $product, array $optionIds): TestResponse
{
    return test()->post(route('cart.store', $product), ['option_ids' => $optionIds, 'quantity' => 1]);
}

function cartCookieItems(TestResponse $response): array
{
    $cookie = $response->getCookie('cartItems');

    return $cookie === null ? [] : array_values(json_decode($cookie->getValue(), true));
}

test('a complete option selection is added at its variation price', function () {
    [$product, $storage, $color, , $large, $black] = createPhoneWithStorageAndColor();

    $response = addToCart($product, [$storage->id => $large->id, $color->id => $black->id])
        ->assertSessionHasNoErrors();

    expect(cartCookieItems($response))->toHaveCount(1)
        ->and((float) cartCookieItems($response)[0]['price'])->toBe(220.0);
});

test('logged in users can add, merge, update and remove an item with several options', function () {
    [$product, $storage, $color, , $large, $black] = createPhoneWithStorageAndColor();
    $optionIds = [$storage->id => $large->id, $color->id => $black->id];
    $this->actingAs(App\Models\User::factory()->create());

    $this->post(route('cart.store', $product), ['option_ids' => $optionIds, 'quantity' => 1])->assertSessionHasNoErrors();
    $this->post(route('cart.store', $product), ['option_ids' => $optionIds, 'quantity' => 2])->assertSessionHasNoErrors();

    $cartItem = App\Models\CartItem::sole();
    expect($cartItem->quantity)->toBe(3)
        ->and((float) $cartItem->price)->toBe(220.0);

    $this->put(route('cart.update', $product), ['option_ids' => $optionIds, 'quantity' => 5])->assertSessionHasNoErrors();
    expect($cartItem->fresh()->quantity)->toBe(5);

    $this->delete(route('cart.destroy', $product), ['option_ids' => $optionIds])->assertSessionHasNoErrors();
    expect(App\Models\CartItem::count())->toBe(0);
});

test('a partial option selection is rejected', function () {
    [$product, $storage, , , $large] = createPhoneWithStorageAndColor();

    $response = addToCart($product, [$storage->id => $large->id])
        ->assertSessionHasErrors('option_ids');

    expect(cartCookieItems($response))->toBe([]);
});

test('an option from another product is rejected', function () {
    [$product, $storage, $color, , , $black] = createPhoneWithStorageAndColor();
    [, , , $otherProductOption] = createPhoneWithStorageAndColor();

    $response = addToCart($product, [$storage->id => $otherProductOption->id, $color->id => $black->id])
        ->assertSessionHasErrors('option_ids');

    expect(cartCookieItems($response))->toBe([]);
});

test('an option sent under the wrong variation type is rejected', function () {
    [$product, $storage, $color, $small, , $black] = createPhoneWithStorageAndColor();

    $response = addToCart($product, [$storage->id => $black->id, $color->id => $small->id])
        ->assertSessionHasErrors('option_ids');

    expect(cartCookieItems($response))->toBe([]);
});

test('options are rejected for a product without variations', function () {
    [, , , $small] = createPhoneWithStorageAndColor();
    $plainProduct = createProduct();

    $response = addToCart($plainProduct, [999 => $small->id])
        ->assertSessionHasErrors('option_ids');

    expect(cartCookieItems($response))->toBe([]);
});
