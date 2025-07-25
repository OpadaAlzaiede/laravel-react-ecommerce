import { Head, Link } from "@inertiajs/react";
import { XCircleIcon } from '@heroicons/react/24/solid';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";

export default function Failure() {
    return (
        <AuthenticatedLayout>
            <Head title="Payment Failed" />
            <div className="w-[480px] mx-auto py-8 px-4">
                <div className="flex flex-col gap-2 items-center">
                    <div className="text-6xl text-red-600">
                        <XCircleIcon className="size-24" />
                    </div>
                    <div className="text-3xl font-bold text-center">
                        Payment Failed
                    </div>
                </div>

                <div className="my-6 text-lg text-center text-gray-700 dark:text-gray-300">
                    We were unable to process your payment. Please try again or use a different payment method.
                </div>

                <div className="text-center mt-6 text-sm text-gray-500">
                    If the issue persists, please contact support.
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
