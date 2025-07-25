import Navbar from '@/Components/App/Navbar';
import { usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

export default function AuthenticatedLayout({ header, children }) {
    const props = usePage().props;
    const user = props.auth.user;

    const [showingNavigationDropdown, setShowingNavigationDropdown] =
        useState(false);

    const [successMessages, setSuccessMessages] = useState([]);
    const timeoutRefs = useRef({});

    useEffect(() => {
        if(props.success.message)  {
            const newMessage = {
                ...props.success,
                id: props.success.time
            };

            setSuccessMessages((prevMessages) => [newMessage, ...prevMessages]);

            const timeoutId = setTimeout(() => {
                setSuccessMessages((prevMessages) => prevMessages.filter((msg) => msg.id !== newMessage.id));
                delete timeoutRefs.current[newMessage.id];
            }, 5000);

            timeoutRefs.current[newMessage.id] = timeoutId;
        }
    }, [props.success]);

    return (
        <div className="min-h-screen bg-gray-100">
            <Navbar />

            {props.error && <div className='container mx-auto px-8 mt-8 text-center'>
                <div className="alert alert-error">
                    {props.error}
                </div>
            </div>}

            {successMessages.length > 0 && (
                <div className='toast toast-top toast-end z-[1000] mt-16'>
                    {successMessages.map((msg) => (
                        <div className='alert alert-success' key={msg.id}>
                            <span>{msg.message}</span>
                        </div>
                    ))}
                </div>
            )}
            <main>{children}</main>
        </div>
    );
}
