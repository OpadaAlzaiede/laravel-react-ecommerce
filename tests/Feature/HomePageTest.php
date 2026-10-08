<?php

use App\Enums\Products\ProductStatusEnum;
use App\Enums\Roles\RoleEnum;
use App\Enums\Users\VendorStatusEnum;
use App\Models\User;
use App\Models\Vendor;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

function createHomeVendor(string $storeName): User
{
    Role::findOrCreate(RoleEnum::VENDOR->value);

    $user = User::factory()->create()->assignRole(RoleEnum::VENDOR->value);

    Vendor::create([
        'user_id' => $user->id,
        'status' => VendorStatusEnum::APPROVED->value,
        'store_name' => $storeName,
    ]);

    return $user;
}

test('the home page shows the newest published products first', function () {
    $vendor = createHomeVendor('tech-store');

    foreach (range(1, 5) as $day) {
        createProduct(['title' => "Product {$day}", 'created_by' => $vendor->id, 'created_at' => now()->subDays($day)]);
    }
    createProduct(['title' => 'Draft', 'created_by' => $vendor->id, 'status' => ProductStatusEnum::DRAFT->value]);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('newProducts.data', fn ($products) => collect($products)->pluck('title')->all() === ['Product 1', 'Product 2', 'Product 3', 'Product 4'])
            ->where('products.meta.total', 5)
        );
});

test('the home page shows only featured products in the featured section', function () {
    $vendor = createHomeVendor('tech-store');
    createProduct(['title' => 'Featured Phone', 'created_by' => $vendor->id, 'is_featured' => true]);
    createProduct(['title' => 'Regular Phone', 'created_by' => $vendor->id]);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('featuredProducts.data', 1)
            ->where('featuredProducts.data.0.title', 'Featured Phone')
        );
});

test('the home page lists vendors and categories by product count', function () {
    $vendor = createHomeVendor('tech-store');
    User::factory()->create();

    $phone = createProduct(['created_by' => $vendor->id]);
    createProduct(['created_by' => $vendor->id, 'category_id' => $phone->category_id, 'department_id' => $phone->department_id]);
    $laptop = createProduct(['created_by' => $vendor->id]);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('vendors.data', 1)
            ->where('vendors.data.0.store_name', 'tech-store')
            ->where('categories', fn ($categories) => collect($categories)->pluck('id')->take(2)->all() === [$phone->category_id, $laptop->category_id])
        );
});
