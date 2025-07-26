<?php

namespace App\Http\Controllers;

use App\Enums\Roles\RoleEnum;
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
        $vendor->status = VendorStatusEnum::PENDING->value;
        $vendor->store_name = $request->store_name;
        $vendor->store_address = $request->store_address;
        $vendor->save();

        $user->assignRole(RoleEnum::VENDOR);
    }
}
