    // Pages/Contact.jsx
    import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, useForm } from "@inertiajs/react";

export default function Contact() {
    const { data, setData, post, processing, reset, errors } = useForm({
        name: "",
        email: "",
        subject: "",
        message: "",
    });

    const submit = (e) => {
    e.preventDefault();
    post(route("contact.send"), {
        onSuccess: () => {
        alert("Thank you! We'll get back to you soon.");
        reset();
        },
    });
    };

    return (
    <AuthenticatedLayout>
        <Head title="Contact Us" />

        <div className="bg-base-100 py-12">
            {/* Header */}
            <section className="max-w-4xl mx-auto px-6 lg:px-8 text-center mb-12">
                <h1 className="text-4xl font-bold text-primary mb-4">Get In Touch</h1>
                <p className="text-lg text-base-content/80">
                Have questions? Need help with your order or onboarding? We're here for you.
                </p>
            </section>

            {/* Contact Form & Info */}
            <section className="max-w-6xl mx-auto px-6 lg:px-8">
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-12">
                {/* Contact Form */}
                <form onSubmit={submit} className="space-y-6">
                    <div className="form-control">
                    <label className="label">
                        <span className="label-text">Name</span>
                    </label>
                    <input
                        type="text"
                        className="input input-bordered w-full"
                        value={data.name}
                        onChange={(e) => setData("name", e.target.value)}
                        disabled={processing}
                    />
                    {errors.name && (
                        <span className="text-error text-sm mt-1">{errors.name}</span>
                    )}
                    </div>

                    <div className="form-control">
                    <label className="label">
                        <span className="label-text">Email</span>
                    </label>
                    <input
                        type="email"
                        className="input input-bordered w-full"
                        value={data.email}
                        onChange={(e) => setData("email", e.target.value)}
                        disabled={processing}
                    />
                    {errors.email && (
                        <span className="text-error text-sm mt-1">{errors.email}</span>
                    )}
                    </div>

                    <div className="form-control">
                    <label className="label">
                        <span className="label-text">Subject</span>
                    </label>
                    <input
                        type="text"
                        className="input input-bordered w-full"
                        value={data.subject}
                        onChange={(e) => setData("subject", e.target.value)}
                        disabled={processing}
                    />
                    {errors.subject && (
                        <span className="text-error text-sm mt-1">{errors.subject}</span>
                    )}
                    </div>

                    <div className="form-control">
                    <label className="label">
                        <span className="label-text">Message</span>
                    </label>
                    <textarea
                        className="textarea textarea-bordered w-full"
                        rows="5"
                        value={data.message}
                        onChange={(e) => setData("message", e.target.value)}
                        disabled={processing}
                        placeholder="How can we help you?"
                    ></textarea>
                    {errors.message && (
                        <span className="text-error text-sm mt-1">{errors.message}</span>
                    )}
                    </div>

                    <button
                    type="submit"
                    className="btn btn-primary w-full"
                    disabled={processing}
                    >
                    {processing ? (
                        <span className="loading loading-spinner"></span>
                    ) : (
                        "Send Message"
                    )}
                    </button>
                </form>

                {/* Contact Info */}
                <div className="space-y-8">
                    <div className="flex items-start gap-4">
                    <div className="text-2xl">📧</div>
                    <div>
                        <h3 className="text-lg font-semibold">Email Us</h3>
                        <p className="text-base-content/70">support@tradely.com</p>
                    </div>
                    </div>

                    <div className="flex items-start gap-4">
                    <div className="text-2xl">📞</div>
                    <div>
                        <h3 className="text-lg font-semibold">Call Us</h3>
                        <p className="text-base-content/70">+1 (555) 123-4567</p>
                    </div>
                    </div>

                    <div className="flex items-start gap-4">
                    <div className="text-2xl">🏢</div>
                    <div>
                        <h3 className="text-lg font-semibold">Office</h3>
                        <p className="text-base-content/70">
                        123 Commerce St, Business District<br />
                        Dubai, UAE
                        </p>
                    </div>
                    </div>

                    <div className="flex items-start gap-4">
                    <div className="text-2xl">⏰</div>
                    <div>
                        <h3 className="text-lg font-semibold">Support Hours</h3>
                        <p className="text-base-content/70">
                        Mon-Fri: 9:00 AM - 6:00 PM<br />
                        Weekend: Closed
                        </p>
                    </div>
                    </div>
                </div>
                </div>
            </section>

             {/* Newsletter */}
            <section className="py-16 px-8 mt-14 bg-gradient-to-r from-indigo-600 to-purple-700 text-white">
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
