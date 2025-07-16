<?php

namespace App\Http\Controllers\Auth;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Enums\Roles\RoleEnum;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\CartService;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request, CartService $cartService): HttpFoundationResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = auth()->user();
        $route = "/";

        if($user->hasAnyRole([RoleEnum::ADMIN, RoleEnum::VENDOR])) {
            $cartService->moveCartItemsToDatabase($user->id);
            return Inertia::location(route('filament.admin.pages.dashboard'));
        } else if($user->hasRole(RoleEnum::USER)) {
            $route = route('home', absolute: false);
        }

        $cartService->moveCartItemsToDatabase($user->id);

        return redirect()->intended($route);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
