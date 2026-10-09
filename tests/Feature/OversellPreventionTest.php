<?php

use App\Contracts\Payments\CheckoutGateway;
use App\DTOs\Checkout\CheckoutSessionDto;
use App\Enums\Orders\StatusEnum;
use App\Enums\Products\ProductVariationTypeEnum;
use App\Enums\Users\VendorStatusEnum;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Payments\StripeCheckoutGateway;
use App\Services\StripeWebhookService;
use Illuminate\Database\QueryException;
use Stripe\ApiRequestor;
use Stripe\Event;
use Stripe\StripeClient;
use Tests\Support\FakeStripeHttpClient;

final class SequentialCheckoutGateway implements CheckoutGateway
{
    public int $sessions = 0;

    public bool $shouldFail = false;

    public function createSession(string $customerEmail, array $lineItems, string $successUrl, string $cancelUrl): CheckoutSessionDto
    {
        if ($this->shouldFail) {
            throw new RuntimeException('Stripe is unavailable');
        }

        $this->sessions++;

        return new CheckoutSessionDto("cs_reserve_{$this->sessions}", "https://checkout.stripe.test/{$this->sessions}");
    }
}

function stockedProduct(int $quantity): Product
{
    $vendor = User::factory()->create();
    Vendor::create(['user_id' => $vendor->id, 'status' => VendorStatusEnum::APPROVED->value, 'store_name' => 'store-'.$vendor->id]);

    return createProduct(['title' => 'Galaxy Phone', 'quantity' => $quantity, 'created_by' => $vendor->id, 'updated_by' => $vendor->id]);
}

function completeCheckout(string $sessionId): void
{
    app(StripeWebhookService::class)->handle(Event::constructFrom([
        'type' => 'checkout.session.completed',
        'data' => ['object' => ['id' => $sessionId, 'payment_intent' => 'pi_'.$sessionId]],
    ]));
}

beforeEach(function () {
    $this->gateway = new SequentialCheckoutGateway;
    $this->app->instance(CheckoutGateway::class, $this->gateway);
});

test('two customers cannot buy the same last units', function () {
    $product = stockedProduct(8);
    $firstCustomer = User::factory()->create();
    $secondCustomer = User::factory()->create();

    $this->actingAs($firstCustomer)->post(route('cart.store', $product), ['quantity' => 8])->assertSessionHas('success');
    $this->actingAs($secondCustomer)->post(route('cart.store', $product), ['quantity' => 8])->assertSessionHas('success');

    $this->actingAs($firstCustomer)->post(route('cart.checkout'))->assertRedirect('https://checkout.stripe.test/1');
    $this->actingAs($secondCustomer)
        ->from(route('cart.index'))
        ->post(route('cart.checkout'))
        ->assertRedirect(route('cart.index'))
        ->assertSessionHas('error', 'Galaxy Phone is out of stock.');

    completeCheckout('cs_reserve_1');

    expect($product->fresh()->quantity)->toBe(0)
        ->and(Order::count())->toBe(1)
        ->and(Order::sole()->status)->toBe(StatusEnum::PAID->value)
        ->and($this->gateway->sessions)->toBe(1);
});

test('starting a checkout reserves the stock immediately', function () {
    $product = stockedProduct(8);

    $this->actingAs(User::factory()->create());
    $this->post(route('cart.store', $product), ['quantity' => 3]);
    $this->post(route('cart.checkout'))->assertRedirect();

    expect($product->fresh()->quantity)->toBe(5);

    completeCheckout('cs_reserve_1');

    expect($product->fresh()->quantity)->toBe(5);
});

test('a failed stripe call gives the reserved stock back', function () {
    $product = stockedProduct(8);
    $this->gateway->shouldFail = true;

    $this->actingAs(User::factory()->create());
    $this->post(route('cart.store', $product), ['quantity' => 3]);
    $this->post(route('cart.checkout'))->assertSessionHas('error');

    expect($product->fresh()->quantity)->toBe(8)
        ->and(Order::count())->toBe(0);
});

test('variation stock is reserved and unlimited variations are not limited', function () {
    $product = stockedProduct(0);
    $type = $product->variationTypes()->create(['name' => 'Storage', 'type' => ProductVariationTypeEnum::RADIO->value]);
    $limited = $type->options()->create(['name' => '128GB']);
    $unlimited = $type->options()->create(['name' => '256GB']);
    $limitedVariation = $product->variations()->create(['variation_type_option_ids' => [$limited->id], 'quantity' => 2, 'price' => 120]);
    $unlimitedVariation = $product->variations()->create(['variation_type_option_ids' => [$unlimited->id], 'quantity' => null, 'price' => 220]);

    $this->actingAs(User::factory()->create());
    $this->post(route('cart.store', $product), ['option_ids' => [$type->id => $limited->id], 'quantity' => 2]);
    $this->post(route('cart.store', $product), ['option_ids' => [$type->id => $unlimited->id], 'quantity' => 50]);
    $this->post(route('cart.checkout'))->assertRedirect('https://checkout.stripe.test/1');

    expect($limitedVariation->fresh()->quantity)->toBe(0)
        ->and($unlimitedVariation->fresh()->quantity)->toBeNull()
        ->and($product->fresh()->quantity)->toBe(0);
});

test('stale unpaid checkouts are cancelled and their stock released', function () {
    $product = stockedProduct(8);
    $this->actingAs(User::factory()->create());
    $this->post(route('cart.store', $product), ['quantity' => 3]);
    $this->post(route('cart.checkout'));

    $this->travel(2)->hours();
    $freshCustomer = User::factory()->create();
    $this->actingAs($freshCustomer)->post(route('cart.store', $product), ['quantity' => 1]);
    $this->actingAs($freshCustomer)->post(route('cart.checkout'));

    expect($product->fresh()->quantity)->toBe(4);

    $this->artisan('orders:release-stale-reservations')
        ->expectsOutputToContain('Cancelled 1 stale draft orders.')
        ->assertSuccessful();

    expect($product->fresh()->quantity)->toBe(7)
        ->and(Order::where('status', StatusEnum::CANCELLED->value)->count())->toBe(1)
        ->and(Order::where('status', StatusEnum::DRAFT->value)->count())->toBe(1);
});

test('the database refuses negative stock', function () {
    $product = stockedProduct(1);

    expect(fn () => Product::whereKey($product->id)->update(['quantity' => -1]))
        ->toThrow(QueryException::class);
});

test('stripe checkout sessions expire after thirty minutes', function () {
    $this->freezeTime();
    $stripe = new FakeStripeHttpClient([
        'POST /v1/checkout/sessions' => ['id' => 'cs_test_1', 'object' => 'checkout.session', 'url' => 'https://checkout.stripe.test/1'],
    ]);
    ApiRequestor::setHttpClient($stripe);

    (new StripeCheckoutGateway(new StripeClient('sk_test_fake')))
        ->createSession('buyer@example.com', [], 'https://shop.test/success', 'https://shop.test/cancel');

    ApiRequestor::setHttpClient(null);

    expect((int) $stripe->requests[0]['params']['expires_at'])->toBe(now()->addMinutes(30)->getTimestamp());
});
