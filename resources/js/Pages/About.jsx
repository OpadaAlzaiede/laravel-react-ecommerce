// Pages/About.jsx
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/react";

export default function About() {
    return (
        <AuthenticatedLayout>
            <Head title="About Us" />

            <div className="bg-base-100 py-12">
                {/* Hero Section */}
                <section className="max-w-7xl mx-auto px-6 lg:px-8 text-center mb-16">
                    <h1 className="text-4xl md:text-5xl font-bold text-primary mb-6">
                    About Tradely
                    </h1>
                    <p className="text-lg text-base-content/80 max-w-3xl mx-auto">
                    We connect suppliers and buyers in a seamless, trusted B2B marketplace.
                    Our mission is to empower businesses with efficient, transparent, and scalable procurement tools.
                    </p>
                </section>

                {/* Values */}
                <section className="max-w-6xl mx-auto px-6 lg:px-8 mb-20">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div className="card bg-base-200 shadow-lg p-6 text-center">
                        <div className="text-4xl mb-4">🎯</div>
                        <h3 className="text-xl font-semibold mb-2">Our Mission</h3>
                        <p className="text-base-content/70">
                        To simplify B2B commerce by building a transparent, efficient, and reliable platform for suppliers and buyers.
                        </p>
                    </div>

                    <div className="card bg-base-200 shadow-lg p-6 text-center">
                        <div className="text-4xl mb-4">🤝</div>
                        <h3 className="text-xl font-semibold mb-2">Trusted Partnerships</h3>
                        <p className="text-base-content/70">
                        We vet every supplier and ensure quality, so you can source with confidence.
                        </p>
                    </div>

                    <div className="card bg-base-200 shadow-lg p-6 text-center">
                        <div className="text-4xl mb-4">🚀</div>
                        <h3 className="text-xl font-semibold mb-2">Innovation</h3>
                        <p className="text-base-content/70">
                        We continuously improve our platform with smart tools, real-time data, and seamless UX.
                        </p>
                    </div>
                    </div>
                </section>

                {/* Team or Stats (Optional) */}
                <section className="bg-primary text-primary-content py-12">
                    <div className="max-w-5xl mx-auto text-center px-6">
                    <h2 className="text-3xl font-bold mb-6">Trusted by 1,000+ Businesses</h2>
                    <p className="text-lg opacity-90 mb-8">
                        Join a growing network of suppliers and buyers streamlining their procurement.
                    </p>
                    <div className="grid grid-cols-3 gap-8 text-center">
                        <div>
                        <div className="text-3xl font-bold">500+</div>
                        <div className="text-sm opacity-80">Suppliers</div>
                        </div>
                        <div>
                        <div className="text-3xl font-bold">10K+</div>
                        <div className="text-sm opacity-80">Products</div>
                        </div>
                        <div>
                        <div className="text-3xl font-bold">25+</div>
                        <div className="text-sm opacity-80">Categories</div>
                        </div>
                    </div>
                    </div>
                </section>
            </div>
        </AuthenticatedLayout>
    );
}
