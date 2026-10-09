<?php

use App\Enums\Products\ProductVariationTypeEnum;
use App\Enums\Roles\RoleEnum;
use App\Enums\Users\VendorStatusEnum;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

function createListedProducts(int $count): void
{
    Role::findOrCreate(RoleEnum::VENDOR->value);
    $vendor = User::factory()->create()->assignRole(RoleEnum::VENDOR->value);
    Vendor::create(['user_id' => $vendor->id, 'status' => VendorStatusEnum::APPROVED->value, 'store_name' => 'store-'.$vendor->id]);

    foreach (range(1, $count) as $index) {
        $product = createProduct(['title' => "Phone {$index}", 'is_featured' => true, 'created_by' => $vendor->id, 'updated_by' => $vendor->id]);
        $type = $product->variationTypes()->create(['name' => 'Storage', 'type' => ProductVariationTypeEnum::RADIO->value]);
        $option = $type->options()->create(['name' => '128GB']);
        $product->variations()->create(['variation_type_option_ids' => [$option->id], 'quantity' => 5, 'price' => 120]);
    }
}

function queriesFor(string $url): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();

    test()->get($url)->assertOk();

    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    return $count;
}

test('the number of queries does not grow with the number of products', function (string $routeName) {
    createListedProducts(2);
    $fewProducts = queriesFor(route($routeName));

    createListedProducts(6);
    $manyProducts = queriesFor(route($routeName));

    expect($manyProducts)->toBeLessThanOrEqual($fewProducts + 2);
})->with([
    'home page' => ['dashboard'],
    'product list' => ['products.index'],
]);
