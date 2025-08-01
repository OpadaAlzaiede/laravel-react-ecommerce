import { Link, usePage } from "@inertiajs/react";
import MiniCartDropDown from "./MiniCartDropDown";

export default function Navbar() {

    const {auth, totalQuantity, totalPrice, cartItems} = usePage().props;
    const {user} = auth;
    const userRole = user?.roles[0];

    return (
        <div className="navbar bg-base-100 shadow-sm mx-auto max-w-12xl px-4 py-6 sm:px-2 lg:px-8">
            <div className="flex-1">
                <Link href="/" className="btn btn-ghost text-xl">Tradely</Link>
            </div>
            <div className="flex gap-4">
                <MiniCartDropDown />
                {user && <div className="dropdown dropdown-end">
                    <div tabIndex={0} role="button" className="btn btn-ghost btn-circle avatar">
                        <div className="w-10 rounded-full">
                        <img
                            alt="Tailwind CSS Navbar component"
                            src="https://img.daisyui.com/images/stock/photo-1534528741775-53994a69daeb.webp" />
                        </div>
                    </div>
                    <ul
                        tabIndex={0}
                        className="menu menu-sm dropdown-content bg-base-100 rounded-box z-1 mt-3 w-52 p-2 shadow">
                        <li>
                        <Link href={route('profile.edit')} className="justify-between">
                            Profile
                        </Link>
                        </li>
                        {userRole === 'user' &&
                            <li>
                                <Link href={route('orders.index')} as="button">My orders</Link>
                            </li>}
                        <li>
                            <Link href={route('logout')} method={"post"} as="button">Logout</Link>
                        </li>
                    </ul>
                </div>}
                {!user &&  <>
                    <Link href={route('login')} className="btn">Login</Link>
                    <Link href={route('register')} className="btn btn-primary">Register</Link>
                </>}
            </div>
        </div>
    );
}
