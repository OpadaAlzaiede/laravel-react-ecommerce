<?php

namespace App\Filament\Vendor\Resources\StatsResource\Widgets;

use App\Models\Order;
use App\Models\Product;
use App\Enums\Orders\StatusEnum;
use App\Enums\Products\ProductStatusEnum;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('products', Product::query()->vendor()->where('status', ProductStatusEnum::PUBLISHED->value)->count())
                ->description('Total Products')
                ->descriptionIcon('heroicon-m-tag')
                ->color('success')
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:bg-warning-50',
            ]),
            Stat::make('orders', Order::query()->forVendor()->where('status', StatusEnum::PAID->value)->count())
                ->description('Total Paid Orders')
                ->descriptionIcon('heroicon-m-tag')
                ->color('success')
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:bg-warning-50',
            ]),
            Stat::make('orders', Order::query()->forVendor()->where('status', StatusEnum::DRAFT->value)->count())
                ->description('Total Pending Orders')
                ->descriptionIcon('heroicon-m-tag')
                ->color('success')
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:bg-warning-50',
            ]),
        ];
    }
}
