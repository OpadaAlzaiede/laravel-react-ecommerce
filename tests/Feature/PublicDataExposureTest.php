<?php

use App\Enums\Users\VendorStatusEnum;
use App\Models\User;
use App\Models\Vendor;

beforeEach(function () {
    $this->vendor = User::factory()->create([
        'email' => 'secret-vendor@example.com',
        'stripe_account_id' => 'acct_secret_123',
    ]);

    Vendor::create([
        'user_id' => $this->vendor->id,
        'status' => VendorStatusEnum::APPROVED->value,
        'store_name' => 'tech-store',
        'store_address' => '1 Main St',
    ]);

    $this->product = createProduct(['title' => 'Galaxy Phone', 'created_by' => $this->vendor->id, 'updated_by' => $this->vendor->id]);
});

test('public pages do not expose vendor emails or private vendor fields', function (string $url) {
    $html = $this->get($url)->assertOk()->getContent();

    expect($html)
        ->toContain('tech-store')
        ->not->toContain('secret-vendor@example.com')
        ->not->toContain('acct_secret_123')
        ->not->toContain(VendorStatusEnum::APPROVED->value);
})->with([
    'home' => fn () => route('dashboard'),
    'product list' => fn () => route('products.index'),
    'product page' => fn () => route('products.show', $this->product),
    'vendor list' => fn () => route('vendors.index'),
    'vendor page' => fn () => route('vendors.show', $this->vendor),
]);
