import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import VendorCard from '@/Components/App/VendorCard';

export default function Index({ vendors }) {

    return (
        <AuthenticatedLayout>
            <Head title="All Vendors" />

            <div className="py-8 px-4 sm:px-6 lg:px-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
                <div className="max-w-7xl mx-auto">
                    {/* Page Header */}
                    <div className="mb-8">
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                            All Vendors
                        </h1>
                    </div>

                    {/* vendors Grid */}
                    {vendors.data.length === 0 ? (
                        <div className="text-center py-16">
                            <svg xmlns="http://www.w3.org/2000/svg" className="mx-auto h-16 w-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            <h3 className="mt-4 text-lg font-medium text-gray-900 dark:text-white">
                                No vendors found
                            </h3>
                        </div>
                    ) : (
                        <>
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
                                {vendors.data.map((vendor) => (
                                    <VendorCard key={vendor.id} vendor={vendor} />
                                ))}
                            </div>

                            {/* Pagination */}
                            {vendors.meta?.links && (
                                <div className="flex justify-center">
                                    <nav className="inline-flex -space-x-px">
                                        {vendors.meta.links.map((link, index) => (
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
