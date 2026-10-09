<?php

use App\Contracts\Payments\CheckoutGateway;
use App\DTOs\Checkout\CheckoutSessionDto;
use App\Enums\Products\ProductVariationTypeEnum;
use App\Enums\Users\VendorStatusEnum;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;

function stockProduct(array $attributes = []): Product
{
    $vendor = User::factory()->create();
    Vendor::create(['user_id' => $vendor->id, 'status' => VendorStatusEnum::APPROVED->value, 'store_name' => 'store-'.$vendor->id]);

    return createProduct([...$attributes, 'created_by' => $vendor->id, 'updated_by' => $vendor->id]);
}

beforeEach(function () {
    $this->customer = User::factory()->create();
    $this->actingAs($this->customer);
});

test('adding more than the available stock is rejected', function () {
    $product = stockProduct(['title' => 'Galaxy Phone', 'quantity' => 3]);

    $this->post(route('cart.store', $product), ['quantity' => 4])
        ->assertSessionHas('error', 'Only 3 of Galaxy Phone left in stock.');

    expect(CartItem::count())->toBe(0);
});

test('stock already in the cart counts towards the limit', function () {
    $product = stockProduct(['title' => 'Galaxy Phone', 'quantity' => 3]);

    $this->post(route('cart.store', $product), ['quantity' => 2])->assertSessionHas('success');
    $this->post(route('cart.store', $product), ['quantity' => 2])->assertSessionHas('error');

    expect(CartItem::sole()->quantity)->toBe(2);
});

test('an out of stock product cannot be added', function () {
    $product = stockProduct(['title' => 'Galaxy Phone', 'quantity' => 0]);

    $this->post(route('cart.store', $product))
        ->assertSessionHas('error', 'Galaxy Phone is out of stock.');
});

test('the variation stock is used for products with options', function () {
    $product = stockProduct(['title' => 'Galaxy Phone', 'quantity' => 100]);
    $type = $product->variationTypes()->create(['name' => 'Storage', 'type' => ProductVariationTypeEnum::RADIO->value]);
    $option = $type->options()->create(['name' => '128GB']);
    $product->variations()->create(['variation_type_option_ids' => [$option->id], 'quantity' => 1, 'price' => 120]);

    $this->post(route('cart.store', $product), ['option_ids' => [$type->id => $option->id], 'quantity' => 2])
        ->assertSessionHas('error', 'Only 1 of Galaxy Phone left in stock.');
});

test('variations without a quantity have unlimited stock', function () {
    $product = stockProduct(['quantity' => 0]);
    $type = $product->variationTypes()->create(['name' => 'Storage', 'type' => ProductVariationTypeEnum::RADIO->value]);
    $option = $type->options()->create(['name' => '128GB']);
    $product->variations()->create(['variation_type_option_ids' => [$option->id], 'quantity' => null, 'price' => 120]);

    $this->post(route('cart.store', $product), ['option_ids' => [$type->id => $option->id], 'quantity' => 50])
        ->assertSessionHas('success');
});

test('updating a cart item above the available stock is rejected', function () {
    $product = stockProduct(['title' => 'Galaxy Phone', 'quantity' => 3]);
    $this->post(route('cart.store', $product), ['quantity' => 1]);

    $this->put(route('cart.update', $product), ['option_ids' => [], 'quantity' => 5])
        ->assertSessionHas('error', 'Only 3 of Galaxy Phone left in stock.');

    expect(CartItem::sole()->quantity)->toBe(1);
});

test('checkout is rejected when stock dropped below the cart quantity', function () {
    $product = stockProduct(['title' => 'Galaxy Phone', 'quantity' => 3]);
    $this->post(route('cart.store', $product), ['quantity' => 3]);
    $product->update(['quantity' => 1]);

    $this->app->instance(CheckoutGateway::class, new class implements CheckoutGateway
    {
        public function createSession(string $customerEmail, array $lineItems, string $successUrl, string $cancelUrl): CheckoutSessionDto
        {
            throw new RuntimeException('Stripe should not be called');
        }
    });

    $this->from(route('cart.index'))
        ->post(route('cart.checkout'))
        ->assertRedirect(route('cart.index'))
        ->assertSessionHas('error', 'Only 1 of Galaxy Phone left in stock.');

    expect(Order::count())->toBe(0);
});
