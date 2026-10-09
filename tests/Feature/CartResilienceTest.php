<?php

use App\Enums\Products\ProductVariationTypeEnum;
use App\Enums\Users\VendorStatusEnum;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Inertia\Testing\AssertableInertia as Assert;

function resilienceProduct(string $title, bool $withVendor = true): Product
{
    $owner = User::factory()->create(['name' => 'Owner Name']);

    if ($withVendor) {
        Vendor::create(['user_id' => $owner->id, 'status' => VendorStatusEnum::APPROVED->value, 'store_name' => 'store-'.$owner->id]);
    }

    return createProduct(['title' => $title, 'created_by' => $owner->id, 'updated_by' => $owner->id]);
}

function putInCart(User $customer, Product $product, array $optionIds = []): void
{
    CartItem::create([
        'user_id' => $customer->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'price' => 100,
        'variation_type_option_ids' => $optionIds,
    ]);
}

test('a cart item whose option was deleted does not hide the rest of the cart', function () {
    $customer = User::factory()->create();
    $phone = resilienceProduct('Galaxy Phone');
    $type = $phone->variationTypes()->create(['name' => 'Storage', 'type' => ProductVariationTypeEnum::RADIO->value]);
    $option = $type->options()->create(['name' => '128GB']);
    $shirt = resilienceProduct('Plaid Shirt');

    putInCart($customer, $phone, [$type->id => $option->id]);
    putInCart($customer, $shirt);
    $option->delete();

    $this->actingAs($customer)
        ->get(route('cart.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('totalQuantity', 1)
            ->where('cartItems', fn ($groups) => collect($groups)->flatMap(fn ($group) => $group['items'])->pluck('title')->all() === ['Plaid Shirt'])
        );
});

test('a product whose seller has no vendor record still shows in the cart', function () {
    $customer = User::factory()->create();
    $product = resilienceProduct('Galaxy Phone', withVendor: false);
    putInCart($customer, $product);

    $this->actingAs($customer)
        ->get(route('cart.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('totalQuantity', 1)
            ->where('cartItems', fn ($groups) => collect($groups)->first()['user']['name'] === 'Owner Name')
        );
});
