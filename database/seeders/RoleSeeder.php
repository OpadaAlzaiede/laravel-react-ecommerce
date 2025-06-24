<?php

namespace Database\Seeders;

use App\Enums\Roles\AdminPermissionEnum;
use App\Enums\Roles\RoleEnum;
use App\Enums\Roles\UserPermissionEnum;
use App\Enums\Roles\VendorPermissionEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::create(['name' => RoleEnum::ADMIN->value]);
        $vendorRole = Role::create(['name' => RoleEnum::VENDOR->value]);
        $userRole = Role::create(['name' => RoleEnum::USER->value]);

        $adminPermissions = [];
        $vendorPermissions = [];
        $userPermissions = [];

        foreach(UserPermissionEnum::cases() as $permission) {
            $userPermissions[] = Permission::create(['name' => $permission->value]);
        }

        foreach(VendorPermissionEnum::cases() as $permission) {
            $vendorPermissions[] = Permission::create(['name' => $permission->value]);
        }

        foreach(AdminPermissionEnum::cases() as $permission) {
            $adminPermissions[] = Permission::create(['name' => $permission->value]);
        }

        $adminRole->syncPermissions([
            ...$adminPermissions,
            ...$vendorPermissions,
            ...$userPermissions
        ]);
        $vendorRole->syncPermissions([
            ...$vendorPermissions,
            ...$userPermissions
        ]);
        $userRole->syncPermissions($userPermissions);
    }
}
