<?php

use App\Enums\Products\ProductStatusEnum;
use App\Enums\Users\VendorStatusEnum;
use App\Models\User;
use App\Models\Vendor;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->vendor = User::factory()->create();
    Vendor::create(['user_id' => $this->vendor->id, 'status' => VendorStatusEnum::APPROVED->value, 'store_name' => 'tech-store']);

    $this->published = createProduct(['title' => 'Published Phone', 'created_by' => $this->vendor->id, 'updated_by' => $this->vendor->id]);
    $this->draft = createProduct([
        'title' => 'Draft Phone',
        'status' => ProductStatusEnum::DRAFT->value,
        'category_id' => $this->published->category_id,
        'department_id' => $this->published->department_id,
        'created_by' => $this->vendor->id,
        'updated_by' => $this->vendor->id,
    ]);
});

test('draft products are not shown on the category page or counted', function () {
    $this->get(route('categories.show', $this->published->category))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('category.data.products', fn ($products) => collect($products)->pluck('title')->all() === ['Published Phone'])
        );

    $this->get(route('categories.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('categories.data', fn ($categories) => collect($categories)->firstWhere('id', $this->published->category_id)['products_count'] === 1)
        );
});

test('draft products are not shown on the vendor page or counted', function () {
    $this->get(route('vendors.show', $this->vendor))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('vendor.data.products', fn ($products) => collect($products)->pluck('title')->all() === ['Published Phone'])
        );

    $this->get(route('vendors.index'))
        ->assertInertia(fn (Assert $page) => $page->where('vendors.data.0.products_count', 1));
});

test('a draft product page is not found', function () {
    $this->get(route('products.show', $this->draft))->assertNotFound();
    $this->get(route('products.show', $this->published))->assertOk();
});
