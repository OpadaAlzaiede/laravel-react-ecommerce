<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Vendor;
use App\Services\PayoutService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Throwable;

final class PayVendors extends Command
{
    protected $signature = 'pay:vendors';

    protected $description = 'Perform vendors payouts';

    public function handle(PayoutService $payoutService): int
    {
        $this->info('Starting monthly payout process for vendors.');

        $now = CarbonImmutable::now();

        foreach ($payoutService->eligibleVendors() as $vendor) {
            $this->processPayout($payoutService, $vendor, $now);
        }

        $this->info('Monthly Payout process completed.');

        return self::SUCCESS;
    }

    private function processPayout(PayoutService $payoutService, Vendor $vendor, CarbonImmutable $now): void
    {
        $this->info('Processing payout for vendor:[ID: '.$vendor->id.'] - '.$vendor->store_name);

        try {
            $payout = $payoutService->payVendor($vendor, $now);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return;
        }

        if ($payout === null) {
            $this->info('No orders to process.');

            return;
        }

        $this->info('Payout made with total of: ['.$payout->amount * 100 .']');
    }
}
