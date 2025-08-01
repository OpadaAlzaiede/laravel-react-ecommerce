import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, Link, router, usePage } from "@inertiajs/react";
import { CreditCardIcon, MagnifyingGlassIcon, CalendarIcon } from "@heroicons/react/24/outline";
import Pagination from "@/Components/Core/Pagination";
import { useState, useEffect } from "react";

export default function Index({ orders, filters }) {
    const { flash } = usePage().props; // For success/error messages
    const [search, setSearch] = useState({
        status: filters?.status || "",
        start_date: filters?.start_date || "",
        end_date: filters?.end_date || "",
    });

    // Sync search with backend
    useEffect(() => {
        const delayDebounce = setTimeout(() => {
            router.get(
                route("orders.index"),
                {
                    ...search,
                    page: window.location.search.includes('page=')
                    ? new URLSearchParams(window.location.search).get('page')
                    : 1
                },
                {
                    preserveState: true,
                    replace: true,
                }
            );
        }, 500);

        return () => clearTimeout(delayDebounce);
    }, [search]);

    const clearFilters = () => {
        setSearch({ status: "", start_date: "", end_date: "" });
    };

    return (
        <AuthenticatedLayout>
            <Head title="My Orders" />

            <div className="py-8 px-4 sm:px-6 lg:px-8">
                <div className="max-w-7xl mx-auto">
                    {/* Page Title */}
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                            My Orders
                        </h1>
                        <Link
                            href={route("dashboard")}
                            className="btn btn-sm btn-ghost"
                        >
                            Back to Home
                        </Link>
                    </div>

                    {/* Filters */}
                    <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
                        <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                            Filter Orders
                        </h2>
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                            {/* Status Filter */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Status
                                </label>
                                <select
                                    value={search.status}
                                    onChange={(e) =>
                                        setSearch({
                                            ...search,
                                            status: e.target.value,
                                        })
                                    }
                                    className="input input-bordered w-full"
                                >
                                    <option value="">All Statuses</option>
                                    <option value="draft">Draft</option>
                                    <option value="delivered">Delivered</option>
                                    <option value="cancelled">Cancelled</option>
                                    <option value="shipped">Shipped</option>
                                    <option value="paid">Paid</option>
                                </select>
                            </div>

                            {/* Start Date */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    From Date
                                </label>
                                <div className="relative">
                                    <input
                                        type="date"
                                        value={search.start_date}
                                        onChange={(e) =>
                                            setSearch({
                                                ...search,
                                                start_date: e.target.value,
                                            })
                                        }
                                        className="input input-bordered w-full pl-10"
                                    />
                                    <CalendarIcon className="absolute left-3 top-3 h-4 w-4 text-gray-500 dark:text-gray-400" />
                                </div>
                            </div>

                            {/* End Date */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    To Date
                                </label>
                                <div className="relative">
                                    <input
                                        type="date"
                                        value={search.end_date}
                                        onChange={(e) =>
                                            setSearch({
                                                ...search,
                                                end_date: e.target.value,
                                            })
                                        }
                                        className="input input-bordered w-full pl-10"
                                    />
                                    <CalendarIcon className="absolute left-3 top-3 h-4 w-4 text-gray-500 dark:text-gray-400" />
                                </div>
                            </div>

                            {/* Clear Filters */}
                            <div className="flex items-end">
                                <button
                                    type="button"
                                    onClick={clearFilters}
                                    className="btn btn-ghost w-full"
                                >
                                    Clear
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* Orders List */}
                    {orders.data.length === 0 ? (
                        <div className="text-center py-10">
                            <CreditCardIcon className="mx-auto h-12 w-12 text-gray-400" />
                            <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                                No orders found
                            </h3>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead className="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Order Number
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Vendor
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Total
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Date
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    {orders.data.map((order) => (
                                        <tr key={order.id} className="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                            <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                                #{order.id}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                                {order.vendor}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                                {order.total_price} {order.currency}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span
                                                    className={`inline-flex px-2 text-xs leading-5 font-semibold rounded-full
                                                        ${
                                                            order.status === "paid"
                                                                ? "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200"
                                                                : order.status === "draft"
                                                                ? "bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200"
                                                                : order.status === "cancelled"
                                                                ? "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200"
                                                                : "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                                                        }
                                                    `}
                                                >
                                                    {order.status}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                                {order.created_at}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <Link
                                                    href={route("orders.show", order.id)}
                                                    className="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400"
                                                >
                                                    View Details
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    {/* Pagination */}
                    {orders.data.length > 0 && orders.meta?.links && (
                        <div className="mt-6">
                            <Pagination links={orders.meta.links} />
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
