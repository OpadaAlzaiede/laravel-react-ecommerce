<?php

use App\Enums\Products\ProductVariationTypeEnum;
use App\Enums\Users\VendorStatusEnum;
use App\Models\User;
use App\Models\Vendor;

beforeEach(function () {
    $vendor = User::factory()->create();
    Vendor::create(['user_id' => $vendor->id, 'status' => VendorStatusEnum::APPROVED->value, 'store_name' => 'tech-store']);

    $this->product = createProduct(['title' => 'Galaxy Phone', 'price' => 100, 'created_by' => $vendor->id, 'updated_by' => $vendor->id]);

    $storage = $this->product->variationTypes()->create(['name' => 'Storage', 'type' => ProductVariationTypeEnum::RADIO->value]);
    $this->option = $storage->options()->create(['name' => '128GB']);
    $this->storageType = $storage;

    $this->product->variationTypes()->create(['name' => 'Color', 'type' => ProductVariationTypeEnum::RADIO->value]);
});

test('a variation type without options does not break the first options map', function () {
    expect($this->product->fresh()->getFirstOptionsMap())->toBe([$this->storageType->id => $this->option->id]);
});

test('a product with an empty variation type can be added with its other options', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('cart.store', $this->product), ['option_ids' => [$this->storageType->id => $this->option->id]])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');
});

test('a product with an empty variation type is still listed', function () {
    $this->get(route('products.index'))
        ->assertOk()
        ->assertSee('Galaxy Phone');
});
