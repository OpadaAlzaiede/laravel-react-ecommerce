<?php

namespace App\Enums\Roles;

enum RoleEnum: string
{
    case ADMIN = 'admin';
    case VENDOR = 'vendor';
    case USER = 'user';
}
