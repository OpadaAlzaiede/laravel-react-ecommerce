import React, {useState} from "react";
import {Link, router, useForm} from "@inertiajs/react";
import TextInput from "@/Components/Core/TextInput";
import { productRoute } from "@/helpers";

export default function CartItem({item}) {

    const {data, setData, put} = useForm({
        option_ids: item.option_ids,
        quantity: item.quantity
    });

    const [error, setError] = useState('');

    const onDeleteClick = () => {
        router.delete(route('cart.destroy', item.slug), {
            data: {
                option_ids: item.option_ids,
            },
            preserveScroll: true,
        });
    };

    const handleQuantityChange = (ev) => {
        setError('');
        router.put(route('cart.update', item.slug), {
            quantity: parseInt(ev.target.value),
            option_ids: item.option_ids,
        }, {
            preserveScroll: true,
            onError: (errors) => {
                setError(Object.values(errors)[0]);
            }
        });
    };

    return (
        <>
            <div key={item.id} className={'flex gap-6 p-3'}>
                <Link href={productRoute(item)} className="w-32 min-w-32 min-h-32 flex justify-center self-start">
                    <img src={item.image} alt={item.title} className={'max-w-full max-h-full'} />
                </Link>
                <div className="flex-1 flex flex-col">
                    <div className="flex-1">
                        <h3 className="mb-3 text-sm font-semibold">
                            <Link href={productRoute(item)}>
                                {item.title}
                            </Link>
                        </h3>
                        <div className="text-xs">
                            {item.options.map(option => (
                                <div key={option.id}>
                                    <strong className="text-bold">
                                        {option.type.name}:
                                    </strong>
                                    {option.name}
                                </div>
                            ))}
                        </div>
                    </div>
                    <div className="flex justify-between items-center mt-4">
                        <div className="flex gap-2 items-center">
                            <div className="text-sm">Quantity:</div>
                            <div className={error ? 'tooltip tooltip-open tooltip-error' : ''} data-tip={error}>
                                <TextInput type="number" defaultValue={item.quantity} onBlur={handleQuantityChange} className="input-sm w-16">
                                </TextInput>
                            </div>
                            <button onClick={() => onDeleteClick()} className="btn btn-sm btn-ghost">
                                Delete
                            </button>
                            <button className="btn btn-sm btn-ghost">
                                Save for later
                            </button>
                            <div className="font-bold text-lg">
                                {item.currency}{item.quantity * item.price}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div className="divider"></div>
        </>
    );
}
