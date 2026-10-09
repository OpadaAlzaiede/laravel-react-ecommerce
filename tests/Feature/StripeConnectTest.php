<?php

use App\Enums\Roles\RoleEnum;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Stripe\ApiRequestor;
use Stripe\StripeClient;
use Tests\Support\FakeStripeHttpClient;

function createConnectUser(RoleEnum $role, array $attributes = []): User
{
    Role::findOrCreate($role->value);

    return User::factory()->create($attributes)->assignRole($role->value);
}

function createConnectVendor(array $attributes = []): User
{
    return createConnectUser(RoleEnum::VENDOR, $attributes);
}

beforeEach(function () {
    $this->stripe = new FakeStripeHttpClient([
        'POST /v1/accounts' => ['id' => 'acct_test_1', 'object' => 'account', 'details_submitted' => false],
        'GET /v1/accounts/acct_test_1' => ['id' => 'acct_test_1', 'object' => 'account', 'details_submitted' => true],
        'POST /v1/account_links' => ['object' => 'account_link', 'url' => 'https://connect.stripe.test/onboarding'],
    ]);

    ApiRequestor::setHttpClient($this->stripe);
    (new ReflectionProperty(User::class, 'stripe'))->setValue(null, new StripeClient('sk_test_fake'));
});

afterEach(function () {
    ApiRequestor::setHttpClient(null);
});

test('connecting creates an express account and redirects to stripe onboarding', function () {
    $vendor = createConnectVendor();

    $this->actingAs($vendor)
        ->post(route('stripe.connect'))
        ->assertRedirect('https://connect.stripe.test/onboarding');

    $accountLinkRequest = $this->stripe->requests[1];

    expect($vendor->fresh()->stripe_account_id)->toBe('acct_test_1')
        ->and($this->stripe->requests[0]['params'])->toBe(['type' => 'express'])
        ->and($accountLinkRequest['endpoint'])->toBe('POST /v1/account_links')
        ->and($accountLinkRequest['params']['return_url'])->toBe(route('stripe-connect.return'))
        ->and($accountLinkRequest['params']['refresh_url'])->toBe(route('stripe-connect.refresh'));
});

test('connecting an active account does not start onboarding again', function () {
    $vendor = createConnectVendor(['stripe_account_id' => 'acct_test_1', 'stripe_account_active' => true]);

    $this->actingAs($vendor)
        ->from(route('profile.edit'))
        ->post(route('stripe.connect'))
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHas('success', 'You are already connected to Stripe');

    expect($this->stripe->requests)->toBe([]);
});

test('returning from onboarding stores whether the account is active', function () {
    $vendor = createConnectVendor(['stripe_account_id' => 'acct_test_1']);

    $this->actingAs($vendor)
        ->get(route('stripe-connect.return'))
        ->assertRedirect(route('profile.edit'));

    expect((bool) $vendor->fresh()->stripe_account_active)->toBeTrue()
        ->and($this->stripe->endpoints())->toBe(['GET /v1/accounts/acct_test_1']);
});

test('refreshing onboarding redirects to a new onboarding link', function () {
    $vendor = createConnectVendor(['stripe_account_id' => 'acct_test_1']);

    $this->actingAs($vendor)
        ->get(route('stripe-connect.refresh'))
        ->assertRedirect('https://connect.stripe.test/onboarding');
});

test('guests are sent to login from the onboarding return and refresh pages', function (string $routeName) {
    $this->get(route($routeName))->assertRedirect(route('login'));
})->with(['stripe-connect.return', 'stripe-connect.refresh']);

test('approved vendors can connect to stripe but customers cannot', function () {
    $this->actingAs(createConnectUser(RoleEnum::USER))
        ->post(route('stripe.connect'))
        ->assertForbidden();

    expect($this->stripe->requests)->toBe([]);
});
