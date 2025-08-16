<?php

namespace App\Filament\Resources\EarningsChartResource\Widgets;

use App\Models\Order;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use App\Enums\Orders\StatusEnum;
use Filament\Widgets\ChartWidget;

class EarningsChart extends ChartWidget
{
    protected static ?string $heading = 'Earnings';
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $data = Trend::query(
            Order::query()->where('status', StatusEnum::PAID->value)
            )
            ->between(
                start: now()->startOfYear(),
                end: now()->endOfYear(),
            )
            ->perMonth()
            ->sum('website_commission');

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
        return 'line';
    }
}
