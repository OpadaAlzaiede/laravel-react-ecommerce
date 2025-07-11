import { React, useEffect, useMemo, useState } from 'react';
import { Head, useForm, usePage, router } from '@inertiajs/react';
import Carousel from '@/Components/Core/Carousel';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { arraysAreEqual } from '@/helpers';

export default function Show({ product, variationOptions }) {

    const { data, setData, post } = useForm({
        option_ids: {},
        quantity: 1,
        price: null,
    });

    const {url} = usePage();

    const [selectedOptions, setSelectedOptions] = useState([]);

    const images = useMemo(() => {
        for(let typeId in selectedOptions) {
            const option = selectedOptions[typeId];
            if(option.images.length > 0) {
                return option.images;
            }
        }
        return product.images;
    }, [product, selectedOptions]);

    const computedProduct = useMemo(() => {
        const selectedOptionIds = Object.values(selectedOptions).map(op => op.id).sort();

        for(let variation of product.variations) {
            const optionIds = variation.variation_type_option_ids.sort();

            if(arraysAreEqual(selectedOptionIds, optionIds)) {
                return {
                    'price': variation.price,
                    'quantity': variation.quantity === null ? Number.MAX_VALUE : variation.quantity,
                };
            }
        }

        return {
            'price': product.price,
            'quantity': product.quantity,
        };
    }, [product, selectedOptions]);

    useEffect(() => {
        for(let type of product.variationTypes) {
            const selectedOptionId = variationOptions[type.id];
            chooseOption(
                type.id,
                type.options.find(op => op.id == selectedOptionId) || type.options[0],
                false
            );
        }
    }, []);

    const getOptionIdsMap = (newOptions) => {
        return Object.fromEntries(
            Object.entries(newOptions).map(([a, b]) => [a, b.id])
        );
    };

    const chooseOption = (typeId, option, updateRouter = true) => {
        setSelectedOptions((prevSelectedOptions) => {
            const newOptions = {
                ...prevSelectedOptions,
                [typeId]: option,
            };
            if(updateRouter) {
                const params = new URLSearchParams();
                const optionsMap = getOptionIdsMap(newOptions);

                params.delete('options');

                for (const [key, value] of Object.entries(optionsMap)) {
                    params.append(`options[${key}]`, value);
                }

                const baseUrl = url.split('?')[0];

                router.get(`${baseUrl}?${params.toString()}`, {}, {
                    preserveScroll: true,
                    preserveState: true,
                    replace: true,
                });
            }
            return newOptions;
        });
    };

    const onQuantityChange = (ev) => {
        setData('quantity', parseInt(ev.target.value));
    };

    const addToCart = () => {
        post(route('cart.store', product.id), {
            preserveScroll: true,
            preserveState: true,
            onError: (err) => {
                console.log(err);
            }
        });
    };

    const renderProductVariationTypes = () => {
        return (
            <div className="space-y-4 mt-5">
                {product.variationTypes.map((type) => (
                    <div key={type.id} className="border-b pb-4">
                        <h3 className="font-semibold text-lg">{type.name}</h3>
                        <div className="mt-2 flex gap-2 flex-wrap">
                            {type.type === 'image' &&
                                type.options.map((option) => (
                                    <button
                                        key={option.id}
                                        type="button"
                                        onClick={() => chooseOption(type.id, option)}
                                        className={`p-1 rounded-md transition-all ${
                                            selectedOptions[type.id]?.id === option.id
                                                ? 'ring-2 ring-primary ring-offset-2'
                                                : 'hover:opacity-80'
                                        }`}
                                        aria-label={`Select ${option.name}`}
                                    >
                                        <img
                                            src={option.images?.[0]?.thumb || product.images[0].thumb}
                                            alt={option.name}
                                            className="w-16 h-16 object-cover rounded"
                                        />
                                    </button>
                                ))
                            }
                            {type.type === 'radio' &&
                                type.options.map((option) => (
                                    <label
                                        key={option.id}
                                        className={`flex items-center gap-2 cursor-pointer px-4 py-2 border rounded-full ${
                                            selectedOptions[type.id]?.id === option.id
                                                ? 'bg-primary text-white border-primary'
                                                : 'border-gray-300'
                                        }`}
                                    >
                                        <input
                                            type="radio"
                                            name={`variation_type_${type.id}`}
                                            value={option.id}
                                            checked={selectedOptions[type.id]?.id === option.id}
                                            onChange={() => chooseOption(type.id, option)}
                                            key={option.id}
                                            className="sr-only"
                                        />
                                        <span>{option.name}</span>
                                    </label>
                                ))
                            }
                        </div>
                    </div>
                ))}
            </div>
        );
    };

    const renderAddToCartButton = () => {
        return (
            <div className='mb-8 flex gap-4'>
                <select value={data?.quantity} onChange={onQuantityChange} className='select select-bordered w-full'>
                    {Array.from({
                        length: Math.min(10, computedProduct.quantity),
                    }).map((el, i) => (
                        <option key={i + 1} value={i + 1}>Quantity: {i + 1}</option>
                    ))}
                </select>
                <button onClick={addToCart} className='btn btn-primary'>Add to cart</button>
            </div>
        )
    };

    useEffect(() => {
        const idsMap = Object.fromEntries(
            Object.entries(selectedOptions).map(([typeId, option]) => [typeId, option.id])
        )
        setData('option_ids', idsMap);
    }, [selectedOptions]);

    return (
        <AuthenticatedLayout>
            <Head title={product.title}/>

            <div className="container mx-auto p-8">
                <div className='grid gap-8 grid-cols-1 lg:grid-cols-12'>
                    <div className='col-span-7'>
                        <Carousel images={images}/>
                    </div>
                    <div className='col-span-5'>
                        <h1 className='text-2xl mb-8'>{product.title}</h1>
                        <div>
                            <div className='text-3xl font-semibold'>
                                {product.currency.symbol}{computedProduct.price}
                            </div>
                        </div>
                        {renderProductVariationTypes()}
                        {computedProduct.quantity != undefined && computedProduct.quantity < 10 &&
                            <div className='text-error my-4'>
                                <span>Only {computedProduct.quantity} left in stock</span>
                            </div>
                        }
                        {renderAddToCartButton()}
                        <b className='text-xl'>About the item</b>
                        <div
                            className='wysiwyg-output'
                            dangerouslySetInnerHTML={{__html: product.description}}
                        />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
