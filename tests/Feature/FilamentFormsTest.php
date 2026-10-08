<?php

use App\Enums\Products\ProductStatusEnum;
use App\Enums\Products\ProductVariationTypeEnum;
use App\Enums\Roles\RoleEnum;
use App\Filament\Resources\CurrencyResource\Pages\CreateCurrency;
use App\Filament\Resources\DepartmentResource\Pages\CreateDepartment;
use App\Filament\Resources\DepartmentResource\Pages\EditDepartment;
use App\Filament\Resources\DepartmentResource\RelationManagers\CategoriesRelationManager;
use App\Filament\Vendor\Resources\ProductResource\Pages\CreateProduct;
use App\Filament\Vendor\Resources\ProductResource\Pages\ProductVariationTypes;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Department;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function actingAsPanelUser(string $panel, RoleEnum $role): User
{
    $user = User::factory()->create()->assignRole($role->value);

    test()->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel($panel));

    return $user;
}

test('mass assignment protection is not globally disabled', function () {
    expect(Model::isUnguarded())->toBeFalse();
});

test('vendors can create a product', function () {
    $vendor = actingAsPanelUser('vendor', RoleEnum::VENDOR);
    $existing = createProduct();

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'title' => 'Galaxy S24',
            'slug' => 'galaxy-s24',
            'department_id' => $existing->department_id,
            'category_id' => $existing->category_id,
            'currency_id' => $existing->currency_id,
            'description' => '<p>A new phone.</p>',
            'price' => 899,
            'quantity' => 15,
            'status' => ProductStatusEnum::PUBLISHED->value,
            'is_featured' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Product::where('slug', 'galaxy-s24')->sole())
        ->title->toBe('Galaxy S24')
        ->created_by->toBe($vendor->id)
        ->updated_by->toBe($vendor->id)
        ->is_featured->toBeTrue()
        ->quantity->toBe(15)
        ->category_id->toBe($existing->category_id);
});

test('vendors can add variation types and options to their product', function () {
    $vendor = actingAsPanelUser('vendor', RoleEnum::VENDOR);
    $product = createProduct(['created_by' => $vendor->id, 'updated_by' => $vendor->id]);

    Livewire::test(ProductVariationTypes::class, ['record' => $product->getRouteKey()])
        ->fillForm([
            'variationTypes' => [
                [
                    'name' => 'Storage',
                    'type' => ProductVariationTypeEnum::RADIO->value,
                    'options' => [['name' => '128GB'], ['name' => '256GB']],
                ],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $type = $product->variationTypes()->sole();

    expect($type->name)->toBe('Storage')
        ->and($type->type)->toBe(ProductVariationTypeEnum::RADIO->value)
        ->and($type->options()->pluck('name')->all())->toBe(['128GB', '256GB']);
});

test('admins can create a department', function () {
    actingAsPanelUser('admin', RoleEnum::ADMIN);

    Livewire::test(CreateDepartment::class)
        ->fillForm(['name' => 'Garden', 'slug' => 'garden', 'is_active' => true])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Department::where('slug', 'garden')->sole())
        ->name->toBe('Garden')
        ->is_active->toBeTrue();
});

test('admins can create a currency', function () {
    actingAsPanelUser('admin', RoleEnum::ADMIN);

    Livewire::test(CreateCurrency::class)
        ->fillForm(['name' => 'Pound', 'slug' => 'pound', 'symbol' => '£'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Currency::where('slug', 'pound')->sole()->symbol)->toBe('£');
});

test('admins can create a category in a department', function () {
    actingAsPanelUser('admin', RoleEnum::ADMIN);
    $department = Department::create(['name' => 'Garden', 'slug' => 'garden', 'is_active' => true]);

    Livewire::test(CategoriesRelationManager::class, ['ownerRecord' => $department, 'pageClass' => EditDepartment::class])
        ->callTableAction('create', data: ['name' => 'Plants', 'slug' => 'plants', 'is_active' => true])
        ->assertHasNoTableActionErrors();

    expect(Category::where('slug', 'plants')->sole())
        ->department_id->toBe($department->id)
        ->is_active->toBeTrue();
});
