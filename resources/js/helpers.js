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
