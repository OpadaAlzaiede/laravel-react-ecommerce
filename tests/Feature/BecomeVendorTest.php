<?php

use App\Enums\Users\VendorStatusEnum;
use App\Models\User;
use App\Models\Vendor;

test('a user can request to become a vendor', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->post(route('vendor.store'), ['store_name' => 'my-store', 'store_address' => '1 Main St'])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHas('success');

    expect(Vendor::find($user->id))
        ->status->toBe(VendorStatusEnum::PENDING->value)
        ->store_name->toBe('my-store');
});

test('an approved vendor stays approved after updating their details', function () {
    $user = User::factory()->create();
    Vendor::create([
        'user_id' => $user->id,
        'status' => VendorStatusEnum::APPROVED->value,
        'store_name' => 'my-store',
    ]);

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->post(route('vendor.store'), ['store_name' => 'my-new-store', 'store_address' => '2 Main St'])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHas('success');

    expect(Vendor::find($user->id))
        ->status->toBe(VendorStatusEnum::APPROVED->value)
        ->store_name->toBe('my-new-store')
        ->store_address->toBe('2 Main St');
});

test('a rejected vendor who reapplies goes back to pending', function () {
    $user = User::factory()->create();
    Vendor::create([
        'user_id' => $user->id,
        'status' => VendorStatusEnum::REJECTED->value,
        'store_name' => 'my-store',
    ]);

    $this->actingAs($user)
        ->post(route('vendor.store'), ['store_name' => 'my-store'])
        ->assertRedirect();

    expect(Vendor::find($user->id)->status)->toBe(VendorStatusEnum::PENDING->value);
});

test('a store name with symbols or markup is rejected', function (string $storeName) {
    $this->actingAs(User::factory()->create())
        ->post(route('vendor.store'), ['store_name' => $storeName])
        ->assertSessionHasErrors(['store_name' => 'The store name may only contain letters, numbers, spaces and & \' . -']);
})->with([
    'exclamation mark' => ['My Store!'],
    'html' => ['<b>Store</b>'],
    'starting with a symbol' => ['-Store'],
]);

test('a store name must be unique', function () {
    $owner = User::factory()->create();
    Vendor::create([
        'user_id' => $owner->id,
        'status' => VendorStatusEnum::APPROVED->value,
        'store_name' => 'Taken Store',
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('vendor.store'), ['store_name' => 'Taken Store'])
        ->assertSessionHasErrors('store_name');

    expect(Vendor::find($user->id))->toBeNull();
});

test('readable store names like the demo stores are accepted', function (string $storeName) {
    $this->actingAs(User::factory()->create())
        ->post(route('vendor.store'), ['store_name' => $storeName])
        ->assertSessionHasNoErrors();
})->with([
    'with ampersand' => ['Nova Tech & Home'],
    'with spaces' => ['Maison Market'],
    'with accents and apostrophe' => ["Café d'Été"],
    'slug style' => ['my-store-2'],
]);
