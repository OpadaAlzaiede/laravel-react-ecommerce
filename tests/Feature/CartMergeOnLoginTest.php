<?php

use App\Enums\Products\ProductVariationTypeEnum;
use App\Models\CartItem;
use App\Models\User;

test('logging in merges a guest cart item into the same saved cart item', function (bool $withOptions) {
    $customer = User::factory()->create();
    $product = createProduct(['quantity' => 50]);
    $optionIds = [];

    if ($withOptions) {
        $type = $product->variationTypes()->create(['name' => 'Storage', 'type' => ProductVariationTypeEnum::RADIO->value]);
        $option = $type->options()->create(['name' => '128GB']);
        $optionIds = [$type->id => $option->id];
    }

    CartItem::create([
        'user_id' => $customer->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'price' => 100,
        'variation_type_option_ids' => $optionIds,
    ]);

    $guestCart = [
        $product->id.'_'.json_encode($optionIds) => [
            'id' => 'guest-item',
            'product_id' => $product->id,
            'quantity' => 3,
            'price' => 100,
            'option_ids' => $optionIds,
        ],
    ];

    $this->withCookie('cartItems', json_encode($guestCart))
        ->post(route('login'), ['email' => $customer->email, 'password' => 'password'])
        ->assertRedirect();

    expect(CartItem::count())->toBe(1)
        ->and(CartItem::sole()->quantity)->toBe(5);
})->with([
    'product with options' => [true],
    'product without options' => [false],
]);
