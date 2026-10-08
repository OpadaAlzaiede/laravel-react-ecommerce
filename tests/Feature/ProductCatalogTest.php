<?php

use App\Enums\Products\ProductStatusEnum;
use App\Enums\Users\VendorStatusEnum;
use App\Models\User;
use App\Models\Vendor;
use Inertia\Testing\AssertableInertia as Assert;

function createVendorWithStore(string $storeName): User
{
    $user = User::factory()->create();

    Vendor::create([
        'user_id' => $user->id,
        'status' => VendorStatusEnum::APPROVED->value,
        'store_name' => $storeName,
    ]);

    return $user;
}

test('the catalog lists only published products', function () {
    $vendor = createVendorWithStore('tech-store');
    createProduct(['title' => 'Visible Phone', 'created_by' => $vendor->id]);
    createProduct(['title' => 'Draft Phone', 'created_by' => $vendor->id, 'status' => ProductStatusEnum::DRAFT->value]);

    $this->get(route('products.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Product/Index')
            ->has('products.data', 1)
            ->where('products.data.0.title', 'Visible Phone')
            ->where('filters', ['sort' => 'latest'])
        );
});

test('the catalog filters by title and vendor store name', function () {
    $techStore = createVendorWithStore('tech-store');
    $fashionStore = createVendorWithStore('fashion-store');
    createProduct(['title' => 'Galaxy Phone', 'created_by' => $techStore->id]);
    createProduct(['title' => 'Galaxy Shirt', 'created_by' => $fashionStore->id]);
    createProduct(['title' => 'Laptop', 'created_by' => $techStore->id]);

    $this->get(route('products.index', ['search' => 'Galaxy', 'vendor' => 'tech']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.title', 'Galaxy Phone')
            ->where('filters', ['search' => 'Galaxy', 'vendor' => 'tech', 'sort' => 'latest'])
        );
});

test('the catalog sorts by price and date', function (string $sort, array $expectedTitles) {
    $vendor = createVendorWithStore('tech-store');
    createProduct(['title' => 'Mid', 'price' => 50, 'created_by' => $vendor->id, 'created_at' => now()->subDays(2)]);
    createProduct(['title' => 'Cheap', 'price' => 10, 'created_by' => $vendor->id, 'created_at' => now()->subDays(3)]);
    createProduct(['title' => 'Pricey', 'price' => 90, 'created_by' => $vendor->id, 'created_at' => now()->subDay()]);

    $this->get(route('products.index', ['sort' => $sort]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('products.data', fn ($products) => collect($products)->pluck('title')->all() === $expectedTitles)
        );
})->with([
    'latest' => ['latest', ['Pricey', 'Mid', 'Cheap']],
    'oldest' => ['oldest', ['Cheap', 'Mid', 'Pricey']],
    'price low to high' => ['price_low', ['Cheap', 'Mid', 'Pricey']],
    'price high to low' => ['price_high', ['Pricey', 'Mid', 'Cheap']],
]);

test('the catalog rejects an unknown sort', function () {
    $this->get(route('products.index', ['sort' => 'random']))
        ->assertSessionHasErrors('sort');
});

test('the product page passes the selected options through', function () {
    $vendor = createVendorWithStore('tech-store');
    $product = createProduct(['title' => 'Galaxy Phone', 'created_by' => $vendor->id]);

    $this->get(route('products.show', ['product' => $product, 'options' => [3 => '7']]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Product/Show')
            ->where('product.title', 'Galaxy Phone')
            ->where('variationOptions', [3 => 7])
        );
});
