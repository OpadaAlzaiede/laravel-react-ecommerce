<?php

namespace App\Enums\Roles;

enum UserPermissionEnum: string
{
    case BUY_PRODUCTS = 'buy-products';
    case VIEW_PRODUCTS = 'view-products';
    case MAKE_ORDERS = 'make-orders';
    case MAKE_PAYMENTS = 'make-payments';
}
