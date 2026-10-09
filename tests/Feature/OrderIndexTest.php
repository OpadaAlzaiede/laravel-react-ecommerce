<?php

use App\Enums\Orders\StatusEnum;
use App\Models\Order;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function createCustomerOrder(User $customer, StatusEnum $status, string $createdAt): Order
{
    return Order::forceCreate([
        'total_price' => 50,
        'user_id' => $customer->id,
        'vendor_user_id' => User::factory()->create()->id,
        'status' => $status->value,
        'created_at' => $createdAt,
        'updated_at' => $createdAt,
    ]);
}

test('customers only see their own orders, newest first', function () {
    $customer = User::factory()->create();
    $older = createCustomerOrder($customer, StatusEnum::PAID, '2025-01-10 10:00:00');
    $newer = createCustomerOrder($customer, StatusEnum::PAID, '2025-02-10 10:00:00');
    createCustomerOrder(User::factory()->create(), StatusEnum::PAID, '2025-03-10 10:00:00');

    $this->actingAs($customer)
        ->get(route('orders.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Order/Index')
            ->where('orders.data', fn ($orders) => collect($orders)->pluck('id')->all() === [$newer->id, $older->id])
            ->where('filters', [])
        );
});

test('customers can filter their orders by status', function () {
    $customer = User::factory()->create();
    $paid = createCustomerOrder($customer, StatusEnum::PAID, '2025-01-10 10:00:00');
    createCustomerOrder($customer, StatusEnum::DRAFT, '2025-01-11 10:00:00');

    $this->actingAs($customer)
        ->get(route('orders.index', ['status' => 'paid']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('orders.data', 1)
            ->where('orders.data.0.id', $paid->id)
            ->where('filters', ['status' => 'paid'])
        );
});

test('customers can filter their orders by date range', function () {
    $customer = User::factory()->create();
    createCustomerOrder($customer, StatusEnum::PAID, '2025-01-05 10:00:00');
    $inRange = createCustomerOrder($customer, StatusEnum::PAID, '2025-01-15 10:00:00');
    createCustomerOrder($customer, StatusEnum::PAID, '2025-02-05 10:00:00');

    $this->actingAs($customer)
        ->get(route('orders.index', ['start_date' => '2025-01-10', 'end_date' => '2025-01-31']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('orders.data', 1)
            ->where('orders.data.0.id', $inRange->id)
            ->where('filters', ['start_date' => '2025-01-10', 'end_date' => '2025-01-31'])
        );
});

test('orders placed during the end date are included', function () {
    $customer = User::factory()->create();
    $lateOnEndDate = createCustomerOrder($customer, StatusEnum::PAID, '2025-01-31 18:45:00');
    createCustomerOrder($customer, StatusEnum::PAID, '2025-02-01 00:00:00');

    $this->actingAs($customer)
        ->get(route('orders.index', ['start_date' => '2025-01-01', 'end_date' => '2025-01-31']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('orders.data', 1)
            ->where('orders.data.0.id', $lateOnEndDate->id)
        );
});

test('a date range needs both dates to apply', function () {
    $customer = User::factory()->create();
    createCustomerOrder($customer, StatusEnum::PAID, '2025-01-05 10:00:00');
    createCustomerOrder($customer, StatusEnum::PAID, '2025-02-05 10:00:00');

    $this->actingAs($customer)
        ->get(route('orders.index', ['start_date' => '2025-01-10']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('orders.data', 2));
});

test('invalid order filters are rejected', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('orders.index', ['status' => 'lost', 'start_date' => '2025-02-01', 'end_date' => '2025-01-01']))
        ->assertSessionHasErrors(['status', 'end_date']);
});
