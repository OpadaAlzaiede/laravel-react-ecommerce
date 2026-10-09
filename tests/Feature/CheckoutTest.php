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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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
    $this->phoneVariation = $this->phone->variations()->create(['variation_type_option_ids' => [$option->id], 'quantity' => 10, 'price' => 120]);

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

test('checkout charges the current price, not the price when the item was added', function () {
    $this->phoneVariation->update(['price' => 150]);
    $this->shirt->update(['price' => 30]);

    $this->actingAs($this->customer)
        ->post(route('cart.checkout'))
        ->assertRedirect('https://checkout.stripe.test/cs_test_123');

    $lineItems = collect($this->gateway->calls[0]['lineItems'])->keyBy('price_data.product_data.name');

    expect($lineItems['Galaxy Phone']['price_data']['unit_amount'])->toEqual(15000)
        ->and($lineItems['Plaid Shirt']['price_data']['unit_amount'])->toEqual(3000)
        ->and((float) Order::where('vendor_user_id', $this->techVendor->id)->value('total_price'))->toBe(300.0)
        ->and((float) OrderItem::where('product_id', $this->shirt->id)->value('price'))->toBe(30.0);
});

test('line items sent to stripe use whole cents and absolute image urls', function () {
    Storage::fake('public');
    config(['filesystems.disks.public.url' => '/storage']);
    $this->shirt->addMedia(UploadedFile::fake()->image('shirt.jpg'))->toMediaCollection('images');
    $this->shirt->update(['price' => 19.99]);

    $this->actingAs($this->customer)
        ->post(route('cart.checkout'))
        ->assertRedirect('https://checkout.stripe.test/cs_test_123');

    $lineItems = collect($this->gateway->calls[0]['lineItems'])->keyBy('price_data.product_data.name');
    $shirt = $lineItems['Plaid Shirt']['price_data'];
    $phone = $lineItems['Galaxy Phone']['price_data'];

    expect($shirt['unit_amount'])->toBe(1999)
        ->and($shirt['product_data']['images'])->toHaveCount(1)
        ->and($shirt['product_data']['images'][0])->toStartWith('http')
        ->and($phone['product_data'])->not->toHaveKey('images');
});

test('the cart page shows the current price', function () {
    $this->shirt->update(['price' => 30]);

    $this->actingAs($this->customer)
        ->get(route('cart.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where("cartItems.{$this->fashionVendor->id}.items.0.price", fn ($price) => (float) $price === 30.0)
            ->where("cartItems.{$this->fashionVendor->id}.total_price", fn ($total) => (float) $total === 30.0)
        );
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

test('orders are rolled back when stripe fails and the internal error is not shown', function () {
    $this->gateway->shouldFail = true;

    $this->actingAs($this->customer)
        ->from(route('cart.index'))
        ->post(route('cart.checkout'))
        ->assertRedirect(route('cart.index'))
        ->assertSessionHas('error', 'We could not start the payment. Please try again.');

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
