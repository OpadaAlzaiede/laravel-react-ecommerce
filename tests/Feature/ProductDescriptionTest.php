<?php

test('scripts and event handlers are stripped from product descriptions', function () {
    $product = createProduct([
        'description' => '<p>Great phone</p><img src="x" onerror="alert(1)"><script>alert(2)</script><a href="javascript:alert(3)">link</a>',
    ]);

    $description = $product->fresh()->description;

    expect($description)
        ->toContain('<p>Great phone</p>')
        ->not->toContain('onerror')
        ->not->toContain('<script')
        ->not->toContain('javascript:');
});

test('safe formatting in product descriptions is kept', function () {
    $html = '<p>Fast and light.</p><ul><li><strong>Brand:</strong> Apple</li><li><em>Warranty:</em> 1 year</li></ul>';

    $product = createProduct(['description' => $html]);

    expect($product->fresh()->description)->toBe($html);
});

test('the product page never receives unsafe description html', function () {
    $product = createProduct([
        'description' => '<p>Nice</p><img src="x" onerror="alert(1)">',
    ]);

    $this->get(route('products.show', $product))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Product/Show')
            ->where('product.description', fn ($description) => ! str_contains($description, 'onerror'))
        );
});
