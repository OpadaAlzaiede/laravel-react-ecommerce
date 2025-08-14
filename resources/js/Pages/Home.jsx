import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import ProductCard from '@/Components/App/ProductCard';
import CategoryCard from '@/Components/App/CategoryCard';
import { useEffect, useRef } from 'react';
import VendorCard from '@/Components/App/VendorCard';

export default function Home({ newProducts, products, featuredProducts, categories, vendors }) {
    const scrollContainerRef = useRef(null);


    // Horizontal scroll with mouse drag
    useEffect(() => {
        const container = scrollContainerRef.current;
        if (!container) return;

        let isDown = false;
        let startX;
        let scrollLeft;

        const handleMouseDown = (e) => {
            isDown = true;
            startX = e.pageX - container.offsetLeft;
            scrollLeft = container.scrollLeft;
        };

        const handleMouseLeave = () => {
            isDown = false;
        };

        const handleMouseUp = () => {
            isDown = false;
        };

        const handleMouseMove = (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - container.offsetLeft;
            const walk = (x - startX) * 2;
            container.scrollLeft = scrollLeft - walk;
        };

        container.addEventListener('mousedown', handleMouseDown);
        container.addEventListener('mouseleave', handleMouseLeave);
        container.addEventListener('mouseup', handleMouseUp);
        container.addEventListener('mousemove', handleMouseMove);

        return () => {
            container.removeEventListener('mousedown', handleMouseDown);
            container.removeEventListener('mouseleave', handleMouseLeave);
            container.removeEventListener('mouseup', handleMouseUp);
            container.removeEventListener('mousemove', handleMouseMove);
        };
    }, []);

    const scrollHorizontally = (pixels) => {
        if (scrollContainerRef.current) {
            scrollContainerRef.current.scrollBy({
                left: pixels,
                behavior: 'smooth'
            });

            // Add subtle bounce effect after scroll
            setTimeout(() => {
                const el = scrollContainerRef.current;
                el.style.transition = 'transform 0.2s ease-out';
                el.style.transform = 'scale(0.98)';
                setTimeout(() => {
                    el.style.transform = 'scale(1)';
                }, 100);
            }, 300);
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title="Welcome to Our Store" />

            <div className="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-900 dark:to-slate-800">

                {/* Hero Section */}
                <section className="fade-in relative h-96 md:h-[500px] overflow-hidden">
                    <div
                        className="absolute inset-0 bg-cover bg-center"
                        style={{
                            backgroundImage: 'url(https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80)',
                        }}
                    ></div>
                    <div className="absolute inset-0 bg-gradient-to-r from-indigo-900/80 to-purple-900/60"></div>
                    <div className="relative z-10 flex flex-col items-center justify-center h-full text-center text-white px-6">
                        <h1 className="text-4xl md:text-6xl font-bold mb-4 animate-fade-in">
                            Welcome to Tradely
                        </h1>
                        <p className="text-lg md:text-xl mb-8 opacity-90">
                            Discover the best products from top suppliers
                        </p>
                        <Link
                            href={route('products.index')}
                            className="px-8 py-3 bg-white text-indigo-600 font-semibold rounded-full shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300"
                        >
                            Shop Now
                        </Link>
                    </div>
                </section>

                {/* Featured Products - Horizontal Scroll */}
                <section className="py-12 px-6 relative">
                    <div className="max-w-7xl mx-auto">
                        <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                            🌟 Featured Products
                        </h2>

                        {/* Scroll Container */}
                        <div className="relative">
                            {/* Left Arrow */}
                            <button
                                onClick={() => scrollHorizontally(-300)}
                                className="absolute top-1/2 left-0 -translate-y-1/2 z-20 p-2 bg-white dark:bg-gray-700 rounded-full shadow-lg hover:bg-gray-100 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition"
                                style={{ transform: 'translateY(-50%)', marginLeft: '-1rem' }}
                                aria-label="Scroll left"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 text-gray-700 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>

                            {/* Scrollable Area */}
                            <div
                                ref={scrollContainerRef}
                                className="flex gap-6 pb-4 overflow-x-auto scroll-smooth snap-x hide-scrollbar mx-10"
                                style={{ scrollbarWidth: 'none', msOverflowStyle: 'none' }}
                            >
                                {featuredProducts?.data?.length > 0 ? (
                                    featuredProducts.data.map((product) => (
                                        <div key={product.id} className="snap-start flex-shrink-0 w-80">
                                            <ProductCard product={product} />
                                        </div>
                                    ))
                                ) : (
                                    <p className="text-gray-500">No featured products.</p>
                                )}
                            </div>

                            {/* Right Arrow */}
                            <button
                                onClick={() => scrollHorizontally(300)}
                                className="absolute top-1/2 right-0 -translate-y-1/2 z-20 p-2 bg-white dark:bg-gray-700 rounded-full shadow-lg hover:bg-gray-100 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition"
                                style={{ transform: 'translateY(-50%)', marginRight: '-1rem' }}
                                aria-label="Scroll right"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 text-gray-700 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </section>

                {/* Vendors Preview */}
                <section className="py-12 px-6 bg-white dark:bg-gray-800">
                    <div className="max-w-7xl mx-auto">
                        <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                            📦 Browse by Vendor
                        </h2>
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                            {vendors.data.slice(0, 8).map((vendor) => (
                                <VendorCard key={vendor.id} vendor={vendor} />
                            ))}
                        </div>
                        <div className="mt-8 text-center">
                            <Link
                                href={route('vendors.index')}
                                className="text-indigo-600 hover:text-indigo-800 font-medium"
                            >
                                View All Vendors →
                            </Link>
                        </div>
                    </div>
                </section>

                {/* New Arrivals - Grid */}
                <section className="py-12 px-6 bg-white dark:bg-gray-800">
                    <div className="max-w-7xl mx-auto">
                        <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                            🆕 New Arrivals
                        </h2>
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8 stagger-children">
                            {newProducts.data.map((product) => (
                                <ProductCard key={product.id} product={product} />
                            ))}
                        </div>
                        <div className="mt-10 text-center">
                            <Link
                                href={route('products.index')}
                                className="text-indigo-600 hover:text-indigo-800 font-medium"
                            >
                                View All Products →
                            </Link>
                        </div>
                    </div>
                </section>

                {/* Top Sellers Carousel */}
                <section className="fade-in py-12 px-6">
                    <div className="max-w-7xl mx-auto">
                        <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                            🏆 Top Sellers
                        </h2>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                            {products.data.slice(0, 3).map((product) => (
                                <ProductCard key={product.id} product={product} priority />
                            ))}
                        </div>
                    </div>
                </section>

                {/* Categories Preview */}
                <section className="py-12 px-6 bg-white dark:bg-gray-800">
                    <div className="max-w-7xl mx-auto">
                        <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                            📦 Browse by Category
                        </h2>
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                            {categories.slice(0, 8).map((category) => (
                                <CategoryCard key={category.id} category={category} />
                            ))}
                        </div>
                        <div className="mt-8 text-center">
                            <Link
                                href={route('categories.index')}
                                className="text-indigo-600 hover:text-indigo-800 font-medium"
                            >
                                View All Categories →
                            </Link>
                        </div>
                    </div>
                </section>

                {/* Newsletter */}
                <section className="py-16 px-6 bg-gradient-to-r from-indigo-600 to-purple-700 text-white">
                    <div className="max-w-3xl mx-auto text-center">
                        <h2 className="text-3xl font-bold mb-4">Stay Updated</h2>
                        <p className="mb-6 opacity-90">
                            Subscribe to get the latest product drops and exclusive offers.
                        </p>
                        <div className="flex flex-col sm:flex-row gap-4 max-w-md mx-auto">
                            <input
                                type="email"
                                placeholder="Your email"
                                className="px-4 py-3 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-white/50 w-full"
                            />
                            <button className="px-6 py-3 bg-white text-indigo-600 font-semibold rounded-lg hover:bg-gray-100 transition">
                                Subscribe
                            </button>
                        </div>
                    </div>
                </section>
            </div>
        </AuthenticatedLayout>
    );
}
