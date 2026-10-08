<?php

use App\Enums\Products\ProductVariationTypeEnum;
use Illuminate\Testing\TestResponse;

function createCartPhone(): array
{
    $product = createProduct(['price' => 100]);

    $type = $product->variationTypes()->create([
        'name' => 'Storage',
        'type' => ProductVariationTypeEnum::RADIO->value,
    ]);

    $option = $type->options()->create(['name' => '128GB']);

    $product->variations()->create(['variation_type_option_ids' => [$option->id], 'quantity' => 5, 'price' => 120]);

    return [$product, [$type->id => $option->id]];
}

function guestCart(TestResponse $response): array
{
    return json_decode($response->getCookie('cartItems')->getValue(), true);
}

test('guests can add, update and remove a cart item', function () {
    [$product, $optionIds] = createCartPhone();

    $added = $this->post(route('cart.store', $product), ['option_ids' => $optionIds, 'quantity' => 2])
        ->assertRedirect()
        ->assertSessionHas('success');

    $cart = guestCart($added);
    $item = array_values($cart)[0];
    expect($cart)->toHaveCount(1)
        ->and($item['quantity'])->toBe(2)
        ->and((float) $item['price'])->toBe(120.0)
        ->and($item['option_ids'])->toEqual($optionIds);

    $updated = $this->withCookie('cartItems', json_encode($cart))
        ->put(route('cart.update', $product), ['option_ids' => $optionIds, 'quantity' => 5])
        ->assertRedirect();

    $cart = guestCart($updated);
    expect(array_values($cart)[0]['quantity'])->toBe(5);

    $removed = $this->withCookie('cartItems', json_encode($cart))
        ->delete(route('cart.destroy', $product), ['option_ids' => $optionIds])
        ->assertRedirect();

    expect(guestCart($removed))->toBe([]);
});

test('option ids sent as strings are stored as integers', function () {
    [$product, $optionIds] = createCartPhone();
    $typeId = array_key_first($optionIds);

    $response = $this->post(route('cart.store', $product), ['option_ids' => [$typeId => (string) $optionIds[$typeId]]]);

    expect(array_values(guestCart($response))[0]['option_ids'])->toBe($optionIds);
});

test('a product without variations can be removed without sending option ids', function () {
    $product = createProduct();

    $cart = guestCart($this->post(route('cart.store', $product)));
    expect($cart)->toHaveCount(1);

    $removed = $this->withCookie('cartItems', json_encode($cart))
        ->delete(route('cart.destroy', $product))
        ->assertRedirect();

    expect(guestCart($removed))->toBe([]);
});

test('updating a cart item requires a positive quantity', function (mixed $quantity) {
    [$product, $optionIds] = createCartPhone();

    $this->put(route('cart.update', $product), ['option_ids' => $optionIds, 'quantity' => $quantity])
        ->assertSessionHasErrors('quantity');
})->with([
    'blank' => [null],
    'zero' => [0],
    'negative' => [-2],
    'text' => ['abc'],
]);

test('option ids must be integers', function () {
    [$product] = createCartPhone();

    $this->post(route('cart.store', $product), ['option_ids' => [1 => 'abc']])
        ->assertSessionHasErrors('option_ids.1');
});
