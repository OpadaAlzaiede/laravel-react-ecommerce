<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Vendors\VendorDetailsDto;
use App\Enums\Users\VendorStatusEnum;
use App\Models\User;
use App\Models\Vendor;

final class VendorService
{
    public function saveDetails(User $user, VendorDetailsDto $details): Vendor
    {
        $vendor = $user->vendor ?? new Vendor;

        $vendor->user_id = $user->id;
        $vendor->store_name = $details->storeName;
        $vendor->store_address = $details->storeAddress;

        if ($vendor->status !== VendorStatusEnum::APPROVED->value) {
            $vendor->status = VendorStatusEnum::PENDING->value;
        }

        $vendor->save();

        return $vendor;
    }
}
