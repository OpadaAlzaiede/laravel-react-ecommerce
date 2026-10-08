import { test } from 'node:test';
import assert from 'node:assert/strict';
import { productFiltersChanged, productFilterValue } from '../../resources/js/helpers.js';

test('filters loaded from the server are not treated as a change', () => {
    assert.equal(productFiltersChanged({ search: '', vendor: '', sort: 'latest' }, {}), false);
    assert.equal(productFiltersChanged({ search: 'phone', vendor: '', sort: 'price_low' }, { search: 'phone', sort: 'price_low' }), false);
});

test('a sort from the url is kept when the page loads', () => {
    assert.equal(productFiltersChanged({ search: '', vendor: '', sort: 'price_high' }, { sort: 'price_high' }), false);
});

test('changing search, vendor or sort is treated as a change', () => {
    assert.equal(productFiltersChanged({ search: 'phone', vendor: '', sort: 'latest' }, {}), true);
    assert.equal(productFiltersChanged({ search: '', vendor: 'Nova', sort: 'latest' }, {}), true);
    assert.equal(productFiltersChanged({ search: '', vendor: '', sort: 'oldest' }, { sort: 'latest' }), true);
});

test('clearing filters is treated as a change', () => {
    assert.equal(productFiltersChanged({ search: '', vendor: '', sort: 'latest' }, { search: 'phone', sort: 'price_low' }), true);
});

test('an empty filters array from the server is treated as no filters', () => {
    assert.equal(productFiltersChanged({ search: '', vendor: '', sort: 'latest' }, []), false);
    assert.equal(productFilterValue([], 'sort'), 'latest');
    assert.equal(productFilterValue([], 'search'), '');
    assert.equal(productFilterValue({ sort: 'price_low' }, 'sort'), 'price_low');
    assert.equal(productFilterValue(null, 'vendor'), '');
});
