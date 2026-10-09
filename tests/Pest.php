<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\DatabaseTruncation::class)
    ->in('Concurrency');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

function createProduct(array $attributes = []): App\Models\Product
{
    $department = App\Models\Department::forceCreate([
        'name' => 'Electronics',
        'slug' => 'electronics-'.Illuminate\Support\Str::random(6),
    ]);

    $category = App\Models\Category::forceCreate([
        'name' => 'Smartphones',
        'slug' => 'smartphones-'.Illuminate\Support\Str::random(6),
        'department_id' => $department->id,
    ]);

    $currency = App\Models\Currency::forceCreate([
        'name' => 'US Dollar',
        'slug' => 'us-dollar',
        'symbol' => '$',
    ]);

    $vendorId = $attributes['created_by'] ?? App\Models\User::factory()->create()->id;

    return App\Models\Product::forceCreate(array_merge([
        'title' => 'Test Phone',
        'slug' => 'test-phone-'.Illuminate\Support\Str::random(6),
        'description' => '<p>A phone.</p>',
        'price' => 100,
        'quantity' => 10,
        'status' => App\Enums\Products\ProductStatusEnum::PUBLISHED->value,
        'department_id' => $department->id,
        'category_id' => $category->id,
        'currency_id' => $currency->id,
        'created_by' => $vendorId,
        'updated_by' => $vendorId,
    ], $attributes));
}
