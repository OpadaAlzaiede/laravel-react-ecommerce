<?php

use App\Enums\Orders\StatusEnum;
use App\Enums\Users\VendorStatusEnum;
use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use Inertia\Testing\AssertableInertia as Assert;

function createOrderFor(User $customer): Order
{
    $vendorUser = User::factory()->create();

    Vendor::create([
        'user_id' => $vendorUser->id,
        'status' => VendorStatusEnum::APPROVED->value,
        'store_name' => 'Test Store',
        'store_address' => 'Test Address',
    ]);

    return Order::forceCreate([
        'total_price' => 100,
        'user_id' => $customer->id,
        'vendor_user_id' => $vendorUser->id,
        'status' => StatusEnum::PAID->value,
    ]);
}

test('customers can view their own orders', function () {
    $customer = User::factory()->create();
    $order = createOrderFor($customer);

    $this->actingAs($customer)
        ->get(route('orders.show', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Order/Show')
            ->where('order.id', $order->id)
        );
});

test('customers cannot view orders that belong to someone else', function () {
    $order = createOrderFor(User::factory()->create());
    $otherCustomer = User::factory()->create();

    $this->actingAs($otherCustomer)
        ->get(route('orders.show', $order))
        ->assertForbidden();
});

test('guests are redirected to login instead of seeing an order', function () {
    $order = createOrderFor(User::factory()->create());

    $this->get(route('orders.show', $order))
        ->assertRedirect(route('login'));
});
