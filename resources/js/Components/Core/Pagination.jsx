// Components/Core/Pagination.jsx

import { Link } from "@inertiajs/react";

export default function Pagination({ links }) {
    return (
        <nav className="flex gap-2 justify-center">
            {links.map((link, index) =>
                link.url ? (
                    <Link
                        key={index}
                        href={link.url}
                        dangerouslySetInnerHTML={{ __html: link.label }}
                        className={`px-3 py-2 text-sm ${
                            link.active
                                ? "bg-indigo-600 text-white"
                                : "text-indigo-600 bg-white hover:bg-gray-100 dark:bg-gray-800 dark:text-indigo-400"
                        } rounded-md`}
                        style={{ display: "inline-block" }}
                    />
                ) : (
                    <span
                        key={index}
                        dangerouslySetInnerHTML={{ __html: link.label }}
                        className="px-3 py-2 text-sm text-gray-500 opacity-50 cursor-not-allowed"
                    />
                )
            )}
        </nav>
    );
}
