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
