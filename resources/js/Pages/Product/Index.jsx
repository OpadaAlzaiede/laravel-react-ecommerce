import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import ProductCard from '@/Components/App/ProductCard';
import { useEffect, useState } from 'react';

export default function Index({ products, filters }) {
    const [search, setSearch] = useState(filters?.search || '');
    const [vendor, setVendor] = useState(filters?.vendor || '');
    const [sort, setSort] = useState('latest');

    // Sync filters with backend
    useEffect(() => {
        const debounce = setTimeout(() => {
            router.get(

                route('products.index'),
                { search, vendor, sort },
                { preserveState: true, replace: true }
            );
        }, 500);

        return () => clearTimeout(debounce);
    }, [search, vendor, sort]);

    return (
        <AuthenticatedLayout>
            <Head title="All Products" />

            <div className="py-8 px-4 sm:px-6 lg:px-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
                <div className="max-w-7xl mx-auto">
                    {/* Page Header */}
                    <div className="mb-8">
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                            All Products
                        </h1>
                        <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            Browse {products.total} products from our suppliers.
                        </p>
                    </div>

                    {/* Filters & Search */}
                    <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-8 grid grid-cols-1 md:grid-cols-4 gap-4">
                        {/* Search */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Search Products
                            </label>
                            <input
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Search by name..."
                                className="input input-bordered w-full"
                            />
                        </div>

                        {/* Vendor Filter */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                By Vendor
                            </label>
                            <input
                                type="text"
                                value={vendor}
                                onChange={(e) => setVendor(e.target.value)}
                                placeholder="Vendor name"
                                className="input input-bordered w-full"
                            />
                        </div>

                        {/* Sort */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Sort By
                            </label>
                            <select
                                value={sort}
                                onChange={(e) => setSort(e.target.value)}
                                className="input input-bordered w-full"
                            >
                                <option value="latest">Newest</option>
                                <option value="oldest">Oldest</option>
                                <option value="price_low">Price: Low to High</option>
                                <option value="price_high">Price: High to Low</option>
                            </select>
                        </div>

                        {/* Clear Filters */}
                        <div className="flex items-end">
                            <button
                                onClick={() => {
                                    setSearch('');
                                    setVendor('');
                                    setSort('latest');
                                }}
                                className="btn btn-ghost w-full"
                            >
                                Clear
                            </button>
                        </div>
                    </div>

                    {/* Products Grid */}
                    {products.data.length === 0 ? (
                        <div className="text-center py-16">
                            <svg xmlns="http://www.w3.org/2000/svg" className="mx-auto h-16 w-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            <h3 className="mt-4 text-lg font-medium text-gray-900 dark:text-white">
                                No products found
                            </h3>
                            <p className="mt-2 text-gray-500 dark:text-gray-400">
                                Try adjusting your search or filter criteria.
                            </p>
                            <div className="mt-6">
                                <button
                                    onClick={() => {
                                        setSearch('');
                                        setVendor('');
                                        setSort('latest');
                                    }}
                                    className="btn btn-primary"
                                >
                                    Reset Filters
                                </button>
                            </div>
                        </div>
                    ) : (
                        <>
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
                                {products.data.map((product) => (
                                    <ProductCard key={product.id} product={product} />
                                ))}
                            </div>

                            {/* Pagination */}
                            {products.meta?.links && (
                                <div className="flex justify-center">
                                    <nav className="inline-flex -space-x-px">
                                        {products.meta.links.map((link, index) => (
                                            <Link
                                                key={index}
                                                href={link.url || '#'}
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                                className={`px-4 py-2 text-sm border ${
                                                    link.active
                                                        ? 'bg-indigo-600 text-white'
                                                        : 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 border-gray-300 dark:border-gray-600'
                                                } ${
                                                    !link.url ? 'cursor-not-allowed opacity-50' : 'hover:bg-gray-100 dark:hover:bg-gray-600'
                                                } first:rounded-l-md last:rounded-r-md`}
                                            />
                                        ))}
                                    </nav>
                                </div>
                            )}
                        </>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
