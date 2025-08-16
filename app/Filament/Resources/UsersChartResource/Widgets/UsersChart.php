<?php

namespace App\Filament\Resources\UsersChartResource\Widgets;

use App\Models\User;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Filament\Widgets\ChartWidget;
use App\Enums\Users\VendorStatusEnum;

class UsersChart extends ChartWidget
{
    protected static ?string $heading = 'Users';

    protected function getData(): array
    {
        $data = Trend::query(
            User::query()
                ->whereHas('roles', fn($query) => $query->where('name', \App\Enums\Roles\RoleEnum::USER->value))
                ->whereDoesntHave('vendor', fn($query) => $query->where('status', VendorStatusEnum::APPROVED->value))
            )
            ->between(
                start: now()->startOfYear(),
                end: now()->endOfYear(),
            )
            ->perMonth()
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'Users',
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                ],
            ],
            'labels' => $data->map(fn (TrendValue $value) => $value->date),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
