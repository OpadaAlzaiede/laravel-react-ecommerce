<?php

namespace App\Filament\Resources\StatsResource\Widgets;

use App\Enums\Orders\StatusEnum;
use App\Models\User;
use App\Models\Order;
use App\Models\Vendor;
use App\Models\Product;
use App\Enums\Roles\RoleEnum;
use App\Enums\Users\VendorStatusEnum;
use App\Enums\Products\ProductStatusEnum;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('vendors', Vendor::query()->where('status', VendorStatusEnum::APPROVED->value)->count())
                ->description('Total Approved Vendors')
                ->descriptionIcon('heroicon-m-tag')
                ->color('success')
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:bg-warning-50',
            ]),
            Stat::make('users', User::query()
                    ->whereHas('roles', fn($query) => $query->where('name', RoleEnum::USER->value))
                    ->whereDoesntHave('vendor', fn($query) => $query->where('status', VendorStatusEnum::APPROVED->value))
                    ->count()
                )
                ->description('Total Users')
                ->descriptionIcon('heroicon-m-tag')
                ->color('success')
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:bg-warning-50',
            ]),
            Stat::make('products', Product::query()->where('status', ProductStatusEnum::PUBLISHED->value)->count())
                ->description('Total Products')
                ->descriptionIcon('heroicon-m-tag')
                ->color('success')
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:bg-warning-50',
            ]),
            Stat::make('orders', Order::query()->where('status', StatusEnum::PAID->value)->count())
                ->description('Total Orders')
                ->descriptionIcon('heroicon-m-tag')
                ->color('success')
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:bg-warning-50',
            ]),
        ];
    }
}
