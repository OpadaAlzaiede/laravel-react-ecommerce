import React from "react";
import { Link, useForm } from "@inertiajs/react";

export default function ProductItem({ product }) {


    const { data, setData, post } = useForm({
        option_ids: {},
        quantity: 1,
    });

    const addToCart = () => {
        post(route('cart.store', product.slug), {
            preserveScroll: true,
            preserveState: true,
            onError: (err) => {
                console.log(err);
            }
        });
    };

    return (
        <div className="group relative bg-white rounded-xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">
            {/* Product image with hover effect */}
            <Link href={route('products.show', product.slug)}>
                <div className="relative overflow-hidden aspect-square">
                    <img
                        src={product.image}
                        alt={product.title}
                        className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                    />
                    {/* Quick view overlay */}
                    <div className="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 flex items-center justify-center transition-all duration-300">
                        <span className="text-white opacity-0 group-hover:opacity-100 transform translate-y-4 group-hover:translate-y-0 transition-all duration-300 font-medium">
                            Quick View
                        </span>
                    </div>
                </div>
            </Link>

            {/* Product details */}
            <div className="p-4">
                {/* Department tag */}
                <Link href="/" className="text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">
                    {product.department.name}
                </Link>

                {/* Product title */}
                <h2 className="mt-1 text-lg font-semibold text-gray-900 line-clamp-2 hover:text-indigo-600 transition-colors">
                    <Link href={route('products.show', product.slug)}>
                        {product.title}
                    </Link>
                </h2>

                {/* Creator */}
                <p className="mt-1 text-sm text-gray-500">
                    by <Link href="/" className="hover:underline hover:text-gray-700">{product.user.name}</Link>
                </p>

                {/* Price and CTA */}
                <div className="mt-4 flex items-center justify-between">
                    <span className="text-lg font-bold text-gray-900">
                        {product.currency.symbol}{product.price}
                    </span>
                    <button onClick={addToCart} className="px-4 py-2 bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-sm font-medium rounded-lg hover:from-indigo-600 hover:to-purple-700 transition-all transform hover:scale-105 active:scale-95 shadow-md hover:shadow-lg">
                        Add to Cart
                    </button>
                </div>
            </div>

            {/* Wishlist button (absolute positioned) */}
            <button className="absolute top-3 right-3 p-2 bg-white bg-opacity-80 rounded-full shadow-sm hover:bg-opacity-100 transition-all group/wishlist">
                <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 text-gray-400 group-hover/wishlist:text-red-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
            </button>
        </div>
    );
}
