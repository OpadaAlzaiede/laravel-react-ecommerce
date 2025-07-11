import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import ProductItem from '@/Components/App/ProductItem';

export default function Home({ products }) {

    return (
        <AuthenticatedLayout>
            <Head title="Welcome" />
            <div className="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3 p-8">
                {products.data.map(product => (
                    <ProductItem product={product} key={product.id} />
                ))}
            </div>
        </AuthenticatedLayout>
    );
}
