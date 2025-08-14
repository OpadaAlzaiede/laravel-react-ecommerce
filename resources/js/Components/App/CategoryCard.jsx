// Components/App/CategoryCard.jsx
import { Link } from '@inertiajs/react';

export default function CategoryCard({ category }) {
    return (
        <Link
            href={route('categories.show', category.slug)}
            className="group p-4 bg-white dark:bg-gray-700 rounded-xl shadow hover:shadow-md transition-all duration-300 hover:-translate-y-1 text-center"
        >
            <div className="w-12 h-12 mx-auto bg-indigo-100 dark:bg-indigo-900 rounded-full flex items-center justify-center mb-3">
                <span className="text-indigo-600 dark:text-indigo-300 font-bold text-sm">
                    {category.name.charAt(0).toUpperCase()}
                </span>
            </div>
            <h3 className="text-sm font-medium text-gray-900 dark:text-white">
                {category.name}
            </h3>
            <h3 className="text-sm font-medium text-gray-900 dark:text-white">
                {category.products_count} Products
            </h3>
        </Link>
    );
}
