import { test } from 'node:test';
import assert from 'node:assert/strict';
import { priceAndStockForOptions } from '../../resources/js/helpers.js';

const product = {
    price: '100.0000',
    quantity: 7,
    variations: [
        { variation_type_option_ids: [2, 1], price: '150.0000', quantity: 3 },
        { variation_type_option_ids: [3, 1], price: null, quantity: null },
    ],
};

test('a matching variation uses its own price and stock', () => {
    assert.deepEqual(priceAndStockForOptions(product, [1, 2]), { price: '150.0000', quantity: 3 });
});

test('a variation without a price falls back to the product price', () => {
    assert.equal(priceAndStockForOptions(product, [1, 3]).price, '100.0000');
});

test('a variation without a quantity has unlimited stock', () => {
    assert.equal(priceAndStockForOptions(product, [3, 1]).quantity, Number.POSITIVE_INFINITY);
});

test('without a matching variation the product price and stock are used', () => {
    assert.deepEqual(priceAndStockForOptions(product, []), { price: '100.0000', quantity: 7 });
});

test('the selected option ids are not reordered in place', () => {
    const selected = [2, 1];
    priceAndStockForOptions(product, selected);
    assert.deepEqual(selected, [2, 1]);
});
