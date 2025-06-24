<?php

namespace App\Enums\Roles;

enum VendorPermissionEnum: string
{
    case ADD_PRODUCT = 'add-product';
    case EDIT_PRODUCT = 'edit-product';
    case DELETE_PRODUCT = 'delete-product';
}
