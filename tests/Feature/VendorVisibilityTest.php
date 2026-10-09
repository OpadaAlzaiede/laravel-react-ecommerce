<?php

use App\Enums\Users\VendorStatusEnum;
use App\Models\User;
use App\Models\Vendor;
use Inertia\Testing\AssertableInertia as Assert;

function vendorWithStatus(VendorStatusEnum $status, string $storeName): User
{
    $user = User::factory()->create();

    Vendor::create([
        'user_id' => $user->id,
        'status' => $status->value,
        'store_name' => $storeName,
    ]);

    return $user;
}

test('the vendor list only shows approved vendors', function () {
    vendorWithStatus(VendorStatusEnum::APPROVED, 'approved-store');
    vendorWithStatus(VendorStatusEnum::PENDING, 'pending-store');
    vendorWithStatus(VendorStatusEnum::REJECTED, 'rejected-store');

    $this->get(route('vendors.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('vendors.meta.total', 1)
            ->where('vendors.data.0.store_name', 'approved-store')
        );
});

test('an approved vendor page can be viewed', function () {
    $vendor = vendorWithStatus(VendorStatusEnum::APPROVED, 'approved-store');

    $this->get(route('vendors.show', $vendor))->assertOk();
});

test('vendor pages are not found for pending, rejected or non vendor users', function (?VendorStatusEnum $status) {
    $user = $status === null ? User::factory()->create() : vendorWithStatus($status, 'some-store');

    $this->get(route('vendors.show', $user))->assertNotFound();
})->with([
    'pending vendor' => [VendorStatusEnum::PENDING],
    'rejected vendor' => [VendorStatusEnum::REJECTED],
    'user without a store' => [null],
]);
