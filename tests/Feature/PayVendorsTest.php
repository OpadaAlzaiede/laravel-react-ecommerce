<?php

use App\Enums\Orders\StatusEnum;
use App\Enums\Users\VendorStatusEnum;
use App\Models\Order;
use App\Models\Payout;
use App\Models\User;
use App\Models\Vendor;
use Stripe\ApiRequestor;
use Stripe\StripeClient;
use Tests\Support\FakeStripeHttpClient;

function createPayableVendor(bool $stripeActive = true): User
{
    $user = User::factory()->create([
        'stripe_account_id' => 'acct_vendor_'.fake()->unique()->numberBetween(1, 9999),
        'stripe_account_active' => $stripeActive,
    ]);

    Vendor::create([
        'user_id' => $user->id,
        'status' => VendorStatusEnum::APPROVED->value,
        'store_name' => 'store-'.$user->id,
    ]);

    return $user;
}

function createVendorOrder(User $vendor, StatusEnum $status, float $vendorSubtotal, string $createdAt): Order
{
    return Order::forceCreate([
        'total_price' => $vendorSubtotal,
        'vendor_subtotal' => $vendorSubtotal,
        'user_id' => User::factory()->create()->id,
        'vendor_user_id' => $vendor->id,
        'status' => $status->value,
        'created_at' => $createdAt,
        'updated_at' => $createdAt,
    ]);
}

function fakeStripe(array $responses): FakeStripeHttpClient
{
    $client = new FakeStripeHttpClient($responses);

    ApiRequestor::setHttpClient($client);
    (new ReflectionProperty(User::class, 'stripe'))->setValue(null, new StripeClient('sk_test_fake'));

    return $client;
}

beforeEach(function () {
    $this->travelTo('2026-10-09 12:00:00');
});

afterEach(function () {
    ApiRequestor::setHttpClient(null);
});

test('vendors are paid for paid orders up to the start of last month', function () {
    $stripe = fakeStripe(['POST /v1/transfers' => ['id' => 'tr_test_1', 'object' => 'transfer']]);
    $vendor = createPayableVendor();

    createVendorOrder($vendor, StatusEnum::PAID, 87.21, '2026-08-15 10:00:00');
    createVendorOrder($vendor, StatusEnum::PAID, 43.60, '2026-07-01 10:00:00');
    createVendorOrder($vendor, StatusEnum::PAID, 500, '2026-09-15 10:00:00');
    createVendorOrder($vendor, StatusEnum::DRAFT, 999, '2026-08-01 10:00:00');

    $this->artisan('pay:vendors')
        ->expectsOutputToContain('Payout made with total of')
        ->assertSuccessful();

    $payout = Payout::sole();

    expect($payout->vendor_id)->toBe($vendor->id)
        ->and((float) $payout->amount)->toBe(130.81)
        ->and((string) $payout->until)->toStartWith('2026-09-01 00:00:00')
        ->and($stripe->endpoints())->toBe(['POST /v1/transfers'])
        ->and($stripe->requests[0]['params']['destination'])->toBe($vendor->stripe_account_id)
        ->and($stripe->requests[0]['params']['currency'])->toBe(config('app.currency'));
});

test('the transfer amount is sent to stripe in cents', function (array $subtotals, int $expectedCents) {
    $stripe = fakeStripe(['POST /v1/transfers' => ['id' => 'tr_test_1', 'object' => 'transfer']]);
    $vendor = createPayableVendor();

    foreach ($subtotals as $subtotal) {
        createVendorOrder($vendor, StatusEnum::PAID, $subtotal, '2026-08-15 10:00:00');
    }

    $this->artisan('pay:vendors')->assertSuccessful();

    expect($stripe->requests[0]['params']['amount'])->toBe($expectedCents);
})->with([
    'several orders' => [[87.21, 43.60], 13081],
    'amount that float truncation would round down' => [[19.99], 1999],
]);

test('the stripe transfer id is stored on the payout', function () {
    fakeStripe(['POST /v1/transfers' => ['id' => 'tr_test_42', 'object' => 'transfer']]);
    $vendor = createPayableVendor();
    createVendorOrder($vendor, StatusEnum::PAID, 87.21, '2026-08-15 10:00:00');

    $this->artisan('pay:vendors')->assertSuccessful();

    expect(Payout::sole()->stripe_transfer_id)->toBe('tr_test_42');
});

test('a vendor is not paid twice for the same period', function () {
    fakeStripe(['POST /v1/transfers' => ['id' => 'tr_test_1', 'object' => 'transfer']]);
    $vendor = createPayableVendor();
    createVendorOrder($vendor, StatusEnum::PAID, 87.21, '2026-08-15 10:00:00');

    $this->artisan('pay:vendors')->assertSuccessful();
    $this->artisan('pay:vendors')
        ->expectsOutputToContain('No orders to process.')
        ->assertSuccessful();

    expect(Payout::count())->toBe(1);
});

test('an order placed exactly at a period boundary is paid out only once', function () {
    $stripe = fakeStripe(['POST /v1/transfers' => ['id' => 'tr_test_1', 'object' => 'transfer']]);
    $vendor = createPayableVendor();
    createVendorOrder($vendor, StatusEnum::PAID, 50, '2026-09-01 00:00:00');

    $this->artisan('pay:vendors')->assertSuccessful();

    $this->travelTo('2026-11-09 12:00:00');
    $this->artisan('pay:vendors')->assertSuccessful();

    expect((float) Payout::sum('amount'))->toBe(50.0)
        ->and($stripe->requests)->toHaveCount(1);
});

test('vendors without an active stripe account are skipped', function () {
    $stripe = fakeStripe([]);
    $vendor = createPayableVendor(stripeActive: false);
    createVendorOrder($vendor, StatusEnum::PAID, 87.21, '2026-08-15 10:00:00');

    $this->artisan('pay:vendors')->assertSuccessful();

    expect(Payout::count())->toBe(0)
        ->and($stripe->requests)->toBe([]);
});

test('a failed transfer rolls back the payout and the run continues', function () {
    fakeStripe([]);
    $vendor = createPayableVendor();
    createVendorOrder($vendor, StatusEnum::PAID, 87.21, '2026-08-15 10:00:00');

    $this->artisan('pay:vendors')
        ->expectsOutputToContain('Unexpected Stripe request: POST /v1/transfers')
        ->expectsOutputToContain('Monthly Payout process completed.')
        ->assertSuccessful();

    expect(Payout::count())->toBe(0);
});
