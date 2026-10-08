<?php

use App\Contracts\Payments\CheckoutGateway;
use App\DTOs\Checkout\CheckoutSessionDto;
use App\Enums\Orders\StatusEnum;
use App\Enums\Products\ProductVariationTypeEnum;
use App\Enums\Users\VendorStatusEnum;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;

final class FakeCheckoutGateway implements CheckoutGateway
{
    public array $calls = [];

    public bool $shouldFail = false;

    public function createSession(string $customerEmail, array $lineItems, string $successUrl, string $cancelUrl): CheckoutSessionDto
    {
        if ($this->shouldFail) {
            throw new RuntimeException('Stripe is unavailable');
        }

        $this->calls[] = compact('customerEmail', 'lineItems', 'successUrl', 'cancelUrl');

        return new CheckoutSessionDto('cs_test_123', 'https://checkout.stripe.test/cs_test_123');
    }
}

function createCheckoutVendor(string $storeName): User
{
    $user = User::factory()->create();

    Vendor::create([
        'user_id' => $user->id,
        'status' => VendorStatusEnum::APPROVED->value,
        'store_name' => $storeName,
    ]);

    return $user;
}

function addToDatabaseCart(User $customer, Product $product, int $quantity, float $price, array $optionIds = []): void
{
    CartItem::forceCreate([
        'user_id' => $customer->id,
        'product_id' => $product->id,
        'quantity' => $quantity,
        'price' => $price,
        'variation_type_option_ids' => $optionIds,
    ]);
}

beforeEach(function () {
    $this->gateway = new FakeCheckoutGateway;
    $this->app->instance(CheckoutGateway::class, $this->gateway);

    $this->customer = User::factory()->create(['email' => 'buyer@example.com']);
    $this->techVendor = createCheckoutVendor('tech-store');
    $this->fashionVendor = createCheckoutVendor('fashion-store');

    $this->phone = createProduct(['title' => 'Galaxy Phone', 'price' => 100, 'created_by' => $this->techVendor->id]);
    $type = $this->phone->variationTypes()->create(['name' => 'Storage', 'type' => ProductVariationTypeEnum::RADIO->value]);
    $option = $type->options()->create(['name' => '128GB']);

    $this->shirt = createProduct(['title' => 'Plaid Shirt', 'price' => 25.5, 'created_by' => $this->fashionVendor->id]);

    addToDatabaseCart($this->customer, $this->phone, 2, 120, [$type->id => $option->id]);
    addToDatabaseCart($this->customer, $this->shirt, 1, 25.5);
});

test('checking out creates a draft order per vendor and redirects to stripe', function () {
    $this->actingAs($this->customer)
        ->post(route('cart.checkout'))
        ->assertRedirect('https://checkout.stripe.test/cs_test_123');

    expect(Order::count())->toBe(2)
        ->and(Order::pluck('status')->unique()->all())->toBe([StatusEnum::DRAFT->value])
        ->and(Order::pluck('stripe_session_id')->unique()->all())->toBe(['cs_test_123'])
        ->and((float) Order::where('vendor_user_id', $this->techVendor->id)->value('total_price'))->toBe(240.0)
        ->and(OrderItem::count())->toBe(2);

    $call = $this->gateway->calls[0];
    $phoneLine = collect($call['lineItems'])->firstWhere('price_data.product_data.name', 'Galaxy Phone');

    expect($call['customerEmail'])->toBe('buyer@example.com')
        ->and($call['successUrl'])->toBe(route('stripe.success').'?session_id={CHECKOUT_SESSION_ID}')
        ->and($call['cancelUrl'])->toBe(route('stripe.failure'))
        ->and($phoneLine['price_data']['unit_amount'])->toEqual(12000)
        ->and($phoneLine['price_data']['product_data']['description'])->toBe('Storage: 128GB')
        ->and($phoneLine['quantity'])->toBe(2);
});

test('checking out a single vendor only creates that vendors order', function () {
    $this->actingAs($this->customer)
        ->post(route('cart.checkout'), ['vendor_id' => $this->fashionVendor->id])
        ->assertRedirect('https://checkout.stripe.test/cs_test_123');

    expect(Order::sole()->vendor_user_id)->toBe($this->fashionVendor->id)
        ->and($this->gateway->calls[0]['lineItems'])->toHaveCount(1);
});

test('checking out a vendor that is not in the cart shows an error', function () {
    $this->actingAs($this->customer)
        ->from(route('cart.index'))
        ->post(route('cart.checkout'), ['vendor_id' => 999])
        ->assertRedirect(route('cart.index'))
        ->assertSessionHas('error', 'There are no items from vendor 999 in your cart.');

    expect(Order::count())->toBe(0);
});

test('orders are rolled back when stripe fails', function () {
    $this->gateway->shouldFail = true;

    $this->actingAs($this->customer)
        ->from(route('cart.index'))
        ->post(route('cart.checkout'))
        ->assertRedirect(route('cart.index'))
        ->assertSessionHas('error', 'Stripe is unavailable');

    expect(Order::count())->toBe(0)
        ->and(OrderItem::count())->toBe(0);
});

test('the vendor id must be an integer', function () {
    $this->actingAs($this->customer)
        ->post(route('cart.checkout'), ['vendor_id' => 'abc'])
        ->assertSessionHasErrors('vendor_id');
});

test('guests must log in before checking out', function () {
    $this->post(route('cart.checkout'))
        ->assertRedirect(route('login'));
});
