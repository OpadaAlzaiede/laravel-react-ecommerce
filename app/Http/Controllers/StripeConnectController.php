<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\StripeConnectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class StripeConnectController extends Controller
{
    public function __construct(private readonly StripeConnectService $stripeConnectService) {}

    public function connect(Request $request): RedirectResponse
    {
        $onboardingUrl = $this->stripeConnectService->onboardingUrl($request->user());

        if ($onboardingUrl === null) {
            return back()->with('success', 'You are already connected to Stripe');
        }

        return redirect()->away($onboardingUrl);
    }

    public function returnFromOnboarding(Request $request): RedirectResponse
    {
        $this->stripeConnectService->syncAccountStatus($request->user());

        return redirect()->to($this->stripeConnectService->onboardingCompletedUrl());
    }

    public function refreshOnboarding(Request $request): RedirectResponse
    {
        return redirect()->away($this->stripeConnectService->refreshOnboardingUrl($request->user()));
    }
}
