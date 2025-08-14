// Components/App/ProductCard.jsx
import { Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function ProductCard({ product, priority = false }) {
    const [isWishlisted, setIsWishlisted] = useState(false);
    const [isLoading, setIsLoading] = useState(false);

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
            <div
                className="group bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-1 transform-gpu"
                style={{ transform: 'translateZ(0)' }} // Enable GPU acceleration
            >            {/* Image Carousel */}
            <div className="relative aspect-square overflow-hidden">
                <Link href={route('products.show', product.slug)}>
                    <div className="relative overflow-hidden aspect-square">
                        <img
                            src={product.image}
                            alt={product.title}
                            className={`w-full h-full object-cover transition-transform duration-700 group-hover:scale-105 ${
                                priority ? 'loading="eager"' : 'loading="lazy"'
                            }`}
                        />
                        {/* Quick view overlay */}
                        <div className="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 flex items-center justify-center transition-all duration-300">
                            <span className="text-white opacity-0 group-hover:opacity-100 transform translate-y-4 group-hover:translate-y-0 transition-all duration-300 font-medium">
                                Quick View
                            </span>
                        </div>
                    </div>
                </Link>
                {product.is_featured && (
                    <div className="absolute top-3 left-3 px-2 py-1 bg-gradient-to-r from-yellow-400 to-orange-500 text-white text-xs font-bold rounded-full">
                        Hot
                    </div>
                )}
                <button
                    onClick={() => setIsWishlisted(!isWishlisted)}
                    className="absolute top-3 right-3 p-2 bg-white/80 backdrop-blur-sm rounded-full shadow-md hover:bg-white transition"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        className={`h-5 w-5 transition-colors ${
                            isWishlisted ? 'text-red-500 fill-red-500' : 'text-gray-600'
                        }`}
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        strokeWidth="2"
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
                        />
                    </svg>
                </button>
            </div>

            {/* Content */}
            <div className="p-5">
                <Link href={route('products.show', product.slug)}>
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white line-clamp-2 group-hover:text-indigo-600 transition-colors">
                        {product.title}
                    </h3>
                </Link>
                <p className="text-sm text-gray-500 mt-1">by {product.vendor.store_name}</p>
                <div className="mt-3 flex items-center justify-between">
                    <span className="text-xl font-bold text-gray-900 dark:text-white">
                        {product.price} {product.currency.symbol}
                    </span>
                    <button
                        onClick={addToCart}
                        disabled={isLoading}
                        className="px-4 py-2 bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-sm font-medium rounded-lg hover:from-indigo-600 hover:to-purple-700 disabled:opacity-70 transition-all transform hover:scale-105 active:scale-95 shadow-sm hover:shadow animate-pulse-hover"
                    >
                        {isLoading ? 'Adding...' : 'Add To Cart'}
                    </button>
                </div>
            </div>
        </div>
    );
}
