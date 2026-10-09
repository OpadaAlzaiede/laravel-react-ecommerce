<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\StockService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

final class ReleaseStaleReservations extends Command
{
    private const STALE_AFTER_MINUTES = 60;

    protected $signature = 'orders:release-stale-reservations';

    protected $description = 'Cancel unpaid draft orders and give their reserved stock back';

    public function handle(StockService $stockService): int
    {
        $cutoff = CarbonImmutable::now()->subMinutes(self::STALE_AFTER_MINUTES);

        $cancelled = $stockService->cancelDraftOrders(
            Order::query()->where('created_at', '<', $cutoff),
        );

        $this->info("Cancelled {$cancelled} stale draft orders.");

        return self::SUCCESS;
    }
}
