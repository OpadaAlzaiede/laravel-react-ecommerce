<?php

use App\Enums\Users\VendorStatusEnum;
use App\Models\User;
use App\Models\Vendor;

test('a new vendor keeps its user id as the primary key after saving', function () {
    $user = User::factory()->create();

    $vendor = Vendor::create([
        'user_id' => $user->id,
        'status' => VendorStatusEnum::PENDING->value,
        'store_name' => 'my-store',
    ]);

    expect($vendor->user_id)->toBe($user->id)
        ->and($vendor->getKey())->toBe($user->id)
        ->and($vendor->user->is($user))->toBeTrue();
});
