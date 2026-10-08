<?php

use App\Enums\Products\ProductVariationTypeEnum;
use App\Enums\Roles\RoleEnum;
use App\Filament\Vendor\Resources\ProductResource\Pages\ProductVariations;
use App\Models\ProductVariation;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

function createVendor(): User
{
    Role::findOrCreate(RoleEnum::VENDOR->value);

    return User::factory()->create()->assignRole(RoleEnum::VENDOR->value);
}

function createProductWithVariation(User $vendor, string $optionName, float $price): array
{
    $product = createProduct(['created_by' => $vendor->id, 'updated_by' => $vendor->id]);

    $type = $product->variationTypes()->create([
        'name' => 'Storage',
        'type' => ProductVariationTypeEnum::RADIO->value,
    ]);

    $option = $type->options()->create(['name' => $optionName]);

    $variation = $product->variations()->create([
        'variation_type_option_ids' => [$option->id],
        'quantity' => 5,
        'price' => $price,
    ]);

    return [$product, $variation];
}

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('vendor'));
});

test('vendors can update the variations of their own product', function () {
    $vendor = createVendor();
    [$product, $variation] = createProductWithVariation($vendor, '128GB', 100);

    $this->actingAs($vendor);

    $component = Livewire::test(ProductVariations::class, ['record' => $product->getRouteKey()]);
    $key = array_key_first($component->get('data.variations'));

    $component
        ->set("data.variations.{$key}.price", 150)
        ->set("data.variations.{$key}.quantity", 7)
        ->call('save')
        ->assertHasNoErrors();

    expect($variation->fresh())
        ->price->toEqual(150)
        ->quantity->toBe(7);
});

test('vendors cannot overwrite another vendors variation by tampering with its id', function () {
    $victim = createVendor();
    [, $victimVariation] = createProductWithVariation($victim, '256GB', 999);

    $attacker = createVendor();
    [$attackerProduct, $attackerVariation] = createProductWithVariation($attacker, '128GB', 100);

    $this->actingAs($attacker);

    $component = Livewire::test(ProductVariations::class, ['record' => $attackerProduct->getRouteKey()]);
    $key = array_key_first($component->get('data.variations'));

    $component
        ->set("data.variations.{$key}.id", $victimVariation->id)
        ->set("data.variations.{$key}.price", 1)
        ->set("data.variations.{$key}.quantity", 0)
        ->call('save');

    expect($victimVariation->fresh())
        ->product_id->toBe($victimVariation->product_id)
        ->price->toEqual(999)
        ->quantity->toBe(5)
        ->and(ProductVariation::count())->toBe(2)
        ->and($attackerVariation->fresh()->price)->toEqual(100);
});
