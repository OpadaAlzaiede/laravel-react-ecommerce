<?php

use App\Contracts\Payments\WebhookGateway;
use App\DTOs\Payments\BalanceTransactionDto;
use App\Enums\Orders\StatusEnum;
use App\Enums\Users\VendorStatusEnum;
use App\Mail\CheckoutCompletedMail;
use App\Mail\NewOrderMail;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\Mail;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;

final class FakeWebhookGateway implements WebhookGateway
{
    public ?Event $event = null;

    public ?BalanceTransactionDto $balanceTransaction = null;

    public function constructEvent(string $payload, string $signature): Event
    {
        if ($signature !== 'valid-signature') {
            throw SignatureVerificationException::factory('Invalid signature', $payload, $signature);
        }

        return $this->event;
    }

    public function retrieveBalanceTransaction(string $transactionId): BalanceTransactionDto
    {
        return $this->balanceTransaction;
    }
}

function stripeEvent(string $type, array $object): Event
{
    return Event::constructFrom(['id' => 'evt_test', 'type' => $type, 'data' => ['object' => $object]]);
}

function createWebhookVendor(): User
{
    $vendor = User::factory()->create();

    Vendor::create([
        'user_id' => $vendor->id,
        'status' => VendorStatusEnum::APPROVED->value,
        'store_name' => 'tech-store',
    ]);

    return $vendor;
}

function createWebhookOrder(User $customer, User $vendor, float $total, array $attributes = []): Order
{
    return Order::forceCreate([
        'total_price' => $total,
        'user_id' => $customer->id,
        'vendor_user_id' => $vendor->id,
        'status' => StatusEnum::DRAFT->value,
        ...$attributes,
    ]);
}

function postWebhook(string $signature = 'valid-signature'): Illuminate\Testing\TestResponse
{
    return test()->call('POST', route('stripe.webhook'), [], [], [], ['HTTP_STRIPE_SIGNATURE' => $signature], '{}');
}

beforeEach(function () {
    $this->gateway = new FakeWebhookGateway;
    $this->app->instance(WebhookGateway::class, $this->gateway);
});

test('webhooks with an invalid signature are rejected', function () {
    postWebhook('forged')
        ->assertStatus(400)
        ->assertSee('Invalid payload');
});

test('a completed checkout marks orders paid, reduces stock and clears purchased cart items', function () {
    $customer = User::factory()->create();
    $vendor = createWebhookVendor();
    $phone = createProduct(['created_by' => $vendor->id, 'quantity' => 10]);
    $shirt = createProduct(['created_by' => $vendor->id, 'quantity' => 10]);

    $order = createWebhookOrder($customer, $vendor, 300, ['stripe_session_id' => 'cs_test_1']);
    OrderItem::create(['order_id' => $order->id, 'product_id' => $phone->id, 'price' => 100, 'quantity' => 3, 'variation_type_option_ids' => []]);

    CartItem::forceCreate(['user_id' => $customer->id, 'product_id' => $phone->id, 'quantity' => 3, 'price' => 100, 'variation_type_option_ids' => []]);
    CartItem::forceCreate(['user_id' => $customer->id, 'product_id' => $shirt->id, 'quantity' => 1, 'price' => 20, 'variation_type_option_ids' => []]);

    $this->gateway->event = stripeEvent('checkout.session.completed', ['id' => 'cs_test_1', 'payment_intent' => 'pi_test_1']);

    postWebhook()->assertOk()->assertSee('Webhook processed successfully');

    expect($order->fresh())
        ->status->toBe(StatusEnum::PAID->value)
        ->payment_intent->toBe('pi_test_1')
        ->and($phone->fresh()->quantity)->toBe(7)
        ->and(CartItem::pluck('product_id')->all())->toBe([$shirt->id]);
});

test('an updated charge splits fees between stripe, the platform and the vendor and sends emails', function () {
    Mail::fake();

    $customer = User::factory()->create();
    $vendor = createWebhookVendor();
    $phoneOrder = createWebhookOrder($customer, $vendor, 100, ['payment_intent' => 'pi_test_1']);
    $shirtOrder = createWebhookOrder($customer, $vendor, 50, ['payment_intent' => 'pi_test_1']);

    $this->gateway->balanceTransaction = new BalanceTransactionDto(amount: 15000, stripeFee: 465);
    $this->gateway->event = stripeEvent('charge.updated', ['balance_transaction' => 'txn_test_1', 'payment_intent' => 'pi_test_1']);

    postWebhook()->assertOk();

    expect($phoneOrder->fresh())
        ->online_payment_commission->toEqual(3.1)
        ->website_commission->toEqual(9.69)
        ->vendor_subtotal->toEqual(87.21)
        ->and($shirtOrder->fresh())
        ->online_payment_commission->toEqual(1.55)
        ->vendor_subtotal->toEqual(43.605);

    Mail::assertSent(NewOrderMail::class, 2);
    Mail::assertSent(CheckoutCompletedMail::class, fn (CheckoutCompletedMail $mail) => $mail->hasTo($customer->email));
});

test('unknown webhook events are acknowledged', function () {
    $this->gateway->event = stripeEvent('customer.created', ['id' => 'cus_test_1']);

    postWebhook()->assertOk();
});

test('customers see their orders on the checkout success page', function () {
    $customer = User::factory()->create();
    $order = createWebhookOrder($customer, createWebhookVendor(), 100, ['stripe_session_id' => 'cs_test_1']);

    $this->actingAs($customer)
        ->get(route('stripe.success', ['session_id' => 'cs_test_1']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Stripe/Success')
            ->has('orders', 1)
            ->where('orders.0.id', $order->id)
        );
});

test('the checkout success page is forbidden for other customers', function () {
    createWebhookOrder(User::factory()->create(), createWebhookVendor(), 100, ['stripe_session_id' => 'cs_test_1']);

    $this->actingAs(User::factory()->create())
        ->get(route('stripe.success', ['session_id' => 'cs_test_1']))
        ->assertForbidden();
});

test('the checkout success page returns 404 for an unknown session', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('stripe.success', ['session_id' => 'cs_unknown']))
        ->assertNotFound();
});

test('the checkout success page requires a session id', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('stripe.success'))
        ->assertSessionHasErrors('session_id');
});
