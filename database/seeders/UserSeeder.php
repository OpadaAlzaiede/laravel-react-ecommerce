<?php

namespace Database\Seeders;

use App\Enums\Roles\RoleEnum;
use App\Enums\Users\VendorStatusEnum;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@tradely.com',
        ]);

        $vendor1 = User::factory()->create([
            'name' => 'Vendor1',
            'email' => 'vendor1@tradely.com',
        ]);
        $vendor2 = User::factory()->create([
            'name' => 'Vendor2',
            'email' => 'vendor2@tradely.com',
        ]);

        $user1 = User::factory()->create([
            'name' => 'User1',
            'email' => 'user1@tradely.com',
        ]);

        $user2 = User::factory()->create([
            'name' => 'User2',
            'email' => 'user2@tradely.com',
        ]);

        $user3 = User::factory()->create([
            'name' => 'User3',
            'email' => 'user3@tradely.com',
        ]);

        $admin->assignRole(RoleEnum::ADMIN->value);
        $vendor1->assignRole(RoleEnum::VENDOR->value);
        Vendor::factory()->create([
            'user_id' => $vendor1->id,
            'status' => VendorStatusEnum::APPROVED,
            'store_name' => 'Vendor1 Store',
            'store_address' => fake()->address(),
        ]);
        $vendor2->assignRole(RoleEnum::VENDOR->value);
        Vendor::factory()->create([
            'user_id' => $vendor2->id,
            'status' => VendorStatusEnum::APPROVED,
            'store_name' => 'Vendor2 Store',
            'store_address' => fake()->address(),
        ]);
        $user1->assignRole(RoleEnum::USER->value);
        $user2->assignRole(RoleEnum::USER->value);
        $user3->assignRole(RoleEnum::USER->value);
    }
}
