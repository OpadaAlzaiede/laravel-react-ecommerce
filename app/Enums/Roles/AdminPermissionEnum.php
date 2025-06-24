<?php

namespace App\Enums\Roles;

enum AdminPermissionEnum: string
{
    case APPROVE_VENDORS = 'approve-vendors';
    case DISAPPROVE_VENDORS = 'disapprove-vendors';

    case ADD_ADMIN = 'add-admin';
    case EDIT_ADMIN = 'edit-admin';
    case DELETE_ADMIN = 'delete-admin';

    case ADD_CATEGORY = 'add-category';
    case EDIT_CATEGORY = 'edit-category';
    case DELETE_CATEGORY = 'delete-category';
}
