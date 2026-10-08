<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Vendor\StoreRequest;
use App\Services\VendorService;
use Illuminate\Http\RedirectResponse;

final class VendorController extends Controller
{
    public function __construct(private readonly VendorService $vendorService) {}

    public function store(StoreRequest $request): RedirectResponse
    {
        $vendor = $this->vendorService->saveDetails($request->user(), $request->toDto());

        return back()->with('success', $vendor->wasRecentlyCreated
            ? 'Your vendor request has been submitted.'
            : 'Your vendor details have been updated.');
    }
}
