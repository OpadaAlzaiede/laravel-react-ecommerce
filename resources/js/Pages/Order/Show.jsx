import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, Link } from "@inertiajs/react";
import { CalendarIcon, HomeIcon, TruckIcon, CurrencyDollarIcon, UserIcon } from "@heroicons/react/24/outline";
import { useEffect } from "react";

export default function Show({ order }) {
    const { id, total_price, status, created_at, vendorUser, orderItems } = order;

    // Format status for display
    const getStatusBadgeClass = (status) => {
        switch (status) {
            case 'pending': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
            case 'delivered': return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
            case 'cancelled': return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
            case 'shipped': return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
            default: return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
        }
    };

    // Format date
    const formattedDate = new Date(created_at).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });

    return (
        <AuthenticatedLayout>
            <Head title={`Order #${id}`} />

            <div className="py-8 px-4 sm:px-6 lg:px-8">
                <div className="max-w-4xl mx-auto">
                    {/* Back to Orders */}
                    <div className="mb-6">
                        <Link
                            href={route('orders.index')}
                            className="btn btn-sm btn-ghost flex items-center gap-1 text-sm"
                        >
                            <HomeIcon className="h-4 w-4" />
                            Back to Orders
                        </Link>
                    </div>

                    {/* Order Header */}
                    <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
                        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                                    Order # {id}
                                </h1>
                                <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    Placed on {formattedDate}
                                </p>
                            </div>

                            <span
                                className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${getStatusBadgeClass(status)}`}
                            >
                                {status === 'pending' && <ClockIcon className="h-4 w-4 mr-1" />}
                                {status === 'delivered' && <TruckIcon className="h-4 w-4 mr-1" />}
                                {status === 'cancelled' && <XCircleIcon className="h-4 w-4 mr-1" />}
                                {status === 'shipped' && <TruckIcon className="h-4 w-4 mr-1" />}
                                {status.charAt(0).toUpperCase() + status.slice(1)}
                            </span>
                        </div>

                        {/* Vendor Info */}
                        <div className="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-3">
                                Vendor
                            </h3>
                            <div className="flex items-center gap-3">
                                <UserIcon className="h-8 w-8 text-gray-400" />
                                <span className="font-medium text-gray-900 dark:text-white">
                                    {vendorUser?.store_name || 'Unknown Vendor'}
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Order Items */}
                    <div className="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                        <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                            <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                                Items in this Order
                            </h2>
                        </div>

                        <ul className="divide-y divide-gray-200 dark:divide-gray-700">
                            {orderItems.map((item) => (
                                <li key={item.id} className="p-6 hover:bg-gray-50 dark:hover:bg-gray-750 transition">
                                    <div className="flex flex-col sm:flex-row gap-4">
                                        {/* Product Image */}
                                        <div className="flex-shrink-0">
                                            <img
                                                src={item.product.image || '/images/placeholder.jpg'}
                                                alt={item.product.title.en}
                                                className="h-20 w-20 object-cover rounded-lg border border-gray-300 dark:border-gray-600"
                                            />
                                        </div>

                                        {/* Product Info */}
                                        <div className="flex-1">
                                            <h3 className="text-sm font-medium text-gray-900 dark:text-white line-clamp-2">
                                                {item.product.title.en}
                                            </h3>
                                            <p className="text-sm text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">
                                                {item.product.description.en}
                                            </p>

                                            {/* Options */}
                                            {item.variation_type_option_ids && (
                                                <div className="mt-2 flex flex-wrap gap-2">
                                                    {Object.values(item.variation_type_option_ids).map((optionId) => (
                                                        <span
                                                            key={optionId}
                                                            className="inline-block bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300 text-xs px-2 py-1 rounded"
                                                        >
                                                            Option {optionId}
                                                        </span>
                                                    ))}
                                                </div>
                                            )}
                                        </div>

                                        {/* Quantity & Price */}
                                        <div className="flex flex-col items-end justify-between text-right">
                                            <div>
                                                <p className="text-sm text-gray-900 dark:text-white">
                                                    {item.quantity} × {item.price}
                                                </p>
                                                <p className="text-sm font-semibold text-gray-900 dark:text-white">
                                                    {(item.quantity * item.price).toFixed(2)}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            ))}
                        </ul>

                        {/* Order Total */}
                        <div className="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center">
                            <span className="text-lg font-bold text-gray-900 dark:text-white">
                                Total
                            </span>
                            <span className="text-lg font-bold text-indigo-600 dark:text-indigo-400">
                                {total_price}
                            </span>
                        </div>
                    </div>

                    {/* Print Button */}
                    <div className="mt-6 text-center">
                        <button
                            onClick={() => window.print()}
                            className="btn btn-outline btn-sm"
                        >
                            <PrinterIcon className="h-4 w-4 mr-1" />
                            Print Order
                        </button>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

// Icons
function ClockIcon({ className }) {
    return (
        <svg className={className} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <circle cx="12" cy="12" r="10" strokeWidth="2" />
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 6v6l4 2" />
        </svg>
    );
}

function XCircleIcon({ className }) {
    return (
        <svg className={className} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <circle cx="12" cy="12" r="10" strokeWidth="2" />
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 9l-6 6M9 9l6 6" />
        </svg>
    );
}

function PrinterIcon({ className }) {
    return (
        <svg className={className} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
        </svg>
    );
}
