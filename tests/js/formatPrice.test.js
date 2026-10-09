import { test } from 'node:test';
import assert from 'node:assert/strict';
import { formatPrice } from '../../resources/js/helpers.js';

test('database decimals are shown with two decimals', () => {
    assert.equal(formatPrice('1099.9900', '$'), '$1,099.99');
});

test('floating point noise from multiplication is removed', () => {
    assert.equal(formatPrice(3 * 1199.99, '$'), '$3,599.97');
    assert.equal(formatPrice(3 * 1.1, '$'), '$3.30');
});

test('whole numbers get two decimals', () => {
    assert.equal(formatPrice(25, '€'), '€25.00');
});

test('a missing currency symbol or amount does not break the output', () => {
    assert.equal(formatPrice('10.5'), '10.50');
    assert.equal(formatPrice(null, null), '0.00');
});
