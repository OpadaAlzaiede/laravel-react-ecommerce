<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Enums\Users\VendorStatusEnum;
use App\Http\Requests\Vendor\StoreRequest;

class VendorController extends Controller
{
    public function profile(Vendor $vendor)
    {

    }

    public function store(StoreRequest $request)
    {
        $user = auth()->user();
        $vendor = $user->vendor ?: new Vendor();

        $vendor->user_id = $user->id;
        $vendor->store_name = $request->store_name;
        $vendor->store_address = $request->store_address;

        if ($vendor->status !== VendorStatusEnum::APPROVED->value) {
            $vendor->status = VendorStatusEnum::PENDING->value;
        }

        $vendor->save();

        return back()->with('success', $vendor->wasRecentlyCreated
            ? 'Your vendor request has been submitted.'
            : 'Your vendor details have been updated.');
    }
}
