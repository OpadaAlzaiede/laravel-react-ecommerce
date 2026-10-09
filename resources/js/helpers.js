export const arraysAreEqual = (arr1, arr2) => {

    if(arr1.length !== arr2.length) {
        return false;
    }

    return arr1.every((value, index) => value === arr2[index]);
}


export const productRoute = (product) => {
    const params = new URLSearchParams();

    Object.entries(product.option_ids)
        .forEach(([typeId, optionId]) => {
            params.append(`options[${typeId}]`, optionId+'');
        });

    return route('products.show', product.slug) + '?' + params.toString();
}

const defaultProductFilters = { search: '', vendor: '', sort: 'latest' };

export const productFilterValue = (filters, key) => {
    const isFilterObject = filters && !Array.isArray(filters) && Object.hasOwn(filters, key);

    return (isFilterObject && filters[key]) || defaultProductFilters[key];
}

export const productFiltersChanged = (current, serverFilters = {}) => {
    return Object.keys(defaultProductFilters).some(
        (key) => (current[key] || defaultProductFilters[key]) !== productFilterValue(serverFilters, key)
    );
}

export const priceAndStockForOptions = (product, selectedOptionIds) => {
    const wantedOptionIds = [...selectedOptionIds].sort();

    const variation = product.variations.find((candidate) =>
        arraysAreEqual([...candidate.variation_type_option_ids].sort(), wantedOptionIds)
    );

    if (!variation) {
        return { price: product.price, quantity: product.quantity };
    }

    return {
        price: variation.price ?? product.price,
        quantity: variation.quantity ?? Number.POSITIVE_INFINITY,
    };
}

const priceFormatter = new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

export const formatPrice = (amount, currencySymbol = '') => {
    return `${currencySymbol ?? ''}${priceFormatter.format(Number(amount) || 0)}`;
}
