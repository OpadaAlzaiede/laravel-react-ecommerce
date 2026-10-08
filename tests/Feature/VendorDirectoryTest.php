<?php

use App\Enums\Users\VendorStatusEnum;
use App\Models\User;
use App\Models\Vendor;
use Inertia\Testing\AssertableInertia as Assert;

function createDirectoryVendor(string $storeName): User
{
    $user = User::factory()->create();

    Vendor::create([
        'user_id' => $user->id,
        'status' => VendorStatusEnum::APPROVED->value,
        'store_name' => $storeName,
    ]);

    return $user;
}

test('the vendor list shows only users with a store ordered by product count', function () {
    $smallStore = createDirectoryVendor('small-store');
    $bigStore = createDirectoryVendor('big-store');
    User::factory()->create();

    createProduct(['created_by' => $smallStore->id]);
    createProduct(['created_by' => $bigStore->id]);
    createProduct(['created_by' => $bigStore->id]);

    $this->get(route('vendors.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Vendor/Index')
            ->where('vendors.meta.total', 2)
            ->where('vendors.data.0.store_name', 'big-store')
            ->where('vendors.data.0.products_count', 2)
            ->where('vendors.data.1.store_name', 'small-store')
        );
});

test('the vendor page lists the vendors products', function () {
    $vendor = createDirectoryVendor('tech-store');
    $otherVendor = createDirectoryVendor('other-store');
    createProduct(['title' => 'Galaxy Phone', 'created_by' => $vendor->id]);
    createProduct(['title' => 'Pixel Phone', 'created_by' => $vendor->id]);
    createProduct(['title' => 'Other Phone', 'created_by' => $otherVendor->id]);

    $this->get(route('vendors.show', $vendor))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Vendor/Show')
            ->where('vendor.data.store_name', 'tech-store')
            ->where('vendor.data.products', fn ($products) => collect($products)->pluck('title')->sort()->values()->all() === ['Galaxy Phone', 'Pixel Phone'])
            ->where('filters', [])
        );
});

test('the vendor page filters products by title', function () {
    $vendor = createDirectoryVendor('tech-store');
    createProduct(['title' => 'Galaxy Phone', 'created_by' => $vendor->id]);
    createProduct(['title' => 'Pixel Phone', 'created_by' => $vendor->id]);

    $this->get(route('vendors.show', [$vendor, 'search' => 'Pixel']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('vendor.data.products', 1)
            ->where('vendor.data.products.0.title', 'Pixel Phone')
            ->where('filters', ['search' => 'Pixel'])
        );
});
