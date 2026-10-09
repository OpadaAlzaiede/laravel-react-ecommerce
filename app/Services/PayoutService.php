<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Orders\StatusEnum;
use App\Models\Order;
use App\Models\Payout;
use App\Models\Vendor;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Collection;

final class PayoutService
{
    private const FIRST_PAYOUT_YEAR = 1980;

    private const CENTS_PER_UNIT = 100;

    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly Config $config,
    ) {}

    /**
     * @return Collection<int, Vendor>
     */
    public function eligibleVendors(): Collection
    {
        return Vendor::eligibleForPayout()->get();
    }

    public function payVendor(Vendor $vendor, CarbonImmutable $now): ?Payout
    {
        return $this->db->transaction(function () use ($vendor, $now): ?Payout {
            $startingFrom = Payout::query()
                ->where('vendor_id', $vendor->id)
                ->orderByDesc('until')
                ->value('until') ?? $now->year(self::FIRST_PAYOUT_YEAR)->startOfYear();

            $until = $now->subMonthNoOverflow()->startOfMonth();

            $vendorSubtotal = (float) Order::query()
                ->where('vendor_user_id', $vendor->id)
                ->where('status', StatusEnum::PAID->value)
                ->whereBetween('created_at', [$startingFrom, $until])
                ->sum('vendor_subtotal');

            if ($vendorSubtotal <= 0) {
                return null;
            }

            $payout = Payout::create([
                'vendor_id' => $vendor->id,
                'amount' => $vendorSubtotal,
                'starting_from' => $startingFrom,
                'until' => $until,
            ]);

            $transfer = $vendor->user->transfer($this->toCents($vendorSubtotal), $this->config->get('app.currency'));

            $payout->stripe_transfer_id = $transfer->id;
            $payout->save();

            return $payout;
        });
    }

    private function toCents(float $amount): int
    {
        return (int) round($amount * self::CENTS_PER_UNIT);
    }
}
