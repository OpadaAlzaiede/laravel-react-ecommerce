<?php

use App\Enums\Users\VendorStatusEnum;
use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use Inertia\Testing\AssertableInertia as Assert;

function createCategoryVendor(): User
{
    $user = User::factory()->create();

    Vendor::create([
        'user_id' => $user->id,
        'status' => VendorStatusEnum::APPROVED->value,
        'store_name' => 'tech-store',
    ]);

    return $user;
}

test('the category list is ordered by product count', function () {
    $vendor = createCategoryVendor();
    $phone = createProduct(['created_by' => $vendor->id]);
    createProduct(['created_by' => $vendor->id, 'category_id' => $phone->category_id, 'department_id' => $phone->department_id]);

    $this->get(route('categories.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Category/Index')
            ->where('categories.data.0.id', $phone->category_id)
            ->where('categories.data.0.products_count', 2)
            ->where('categories.meta.total', Category::count())
        );
});

test('the category page lists its products', function () {
    $vendor = createCategoryVendor();
    $phone = createProduct(['title' => 'Galaxy Phone', 'created_by' => $vendor->id]);
    createProduct(['title' => 'Pixel Phone', 'created_by' => $vendor->id, 'category_id' => $phone->category_id, 'department_id' => $phone->department_id]);
    createProduct(['title' => 'Other Category Product', 'created_by' => $vendor->id]);

    $this->get(route('categories.show', $phone->category))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Category/Show')
            ->where('category.data.id', $phone->category_id)
            ->where('category.data.products', fn ($products) => collect($products)->pluck('title')->sort()->values()->all() === ['Galaxy Phone', 'Pixel Phone'])
            ->where('filters', [])
        );
});

test('the category page filters its products by title', function () {
    $vendor = createCategoryVendor();
    $phone = createProduct(['title' => 'Galaxy Phone', 'created_by' => $vendor->id]);
    createProduct(['title' => 'Pixel Phone', 'created_by' => $vendor->id, 'category_id' => $phone->category_id, 'department_id' => $phone->department_id]);

    $this->get(route('categories.show', [$phone->category, 'search' => 'Galaxy']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('category.data.products', 1)
            ->where('category.data.products.0.title', 'Galaxy Phone')
            ->where('filters', ['search' => 'Galaxy'])
        );
});
