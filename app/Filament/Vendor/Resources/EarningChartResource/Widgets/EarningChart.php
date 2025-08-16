<?php

namespace App\Filament\Vendor\Resources\EarningChartResource\Widgets;

use App\Models\Order;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use App\Enums\Orders\StatusEnum;
use Filament\Widgets\ChartWidget;

class EarningChart extends ChartWidget
{
    protected static ?string $heading = 'Earnings';

    protected function getData(): array
    {
        $data = Trend::query(
            Order::query()->forVendor()->where('status', StatusEnum::PAID->value)
            )
            ->between(
                start: now()->startOfYear(),
                end: now()->endOfYear(),
            )
            ->perMonth()
            ->sum('vendor_subtotal');

        return [
            'datasets' => [
                [
                    'label' => 'Earnings',
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
