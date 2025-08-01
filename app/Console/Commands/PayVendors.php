<?php

namespace App\Console\Commands;

use App\Enums\Orders\StatusEnum;
use App\Models\Order;
use App\Models\Payout;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PayVendors extends Command
{

    protected $signature = 'pay:vendors';


    protected $description = 'Perform vendors payouts';


    public function handle()
    {
        $this->info('Starting monthly payout process for vendors.');

        $vendors = Vendor::eligibleForPayout()->get();

        foreach($vendors as $vendor) {
            $this->processPayout($vendor);
        }

        $this->info('Monthly Payout process completed.');

        return Command::SUCCESS;
    }

    protected function processPayout($vendor) {
        $this->info('Processing payout for vendor:[ID: '.$vendor->id.'] - ' .$vendor->store_name);

        try {
            DB::beginTransaction();
            $startingFrom = Payout::where('vendor_id', $vendor->id)->orderBy('until', 'desc')->value('until');

            $startingFrom = $startingFrom ?: now()->year(1980)->startOfYear();
            $until = Carbon::now()->subMonthNoOverflow()->startOfMonth();

            $vendorSubTotal = Order::query()
                        ->where('vendor_user_id', $vendor->id)
                        ->where('status', StatusEnum::PAID->value)
                        ->whereBetween('created_at', [$startingFrom, $until])
                        ->sum('vendor_subtotal');

            if($vendorSubTotal) {
                $this->info('Payout made with total of: [' . $vendorSubTotal * 100 . ']');
                Payout::create([
                    'vendor_id' => $vendor->id,
                    'amount' => $vendorSubTotal,
                    'starting_from' => $startingFrom,
                    'until' => $until,
                ]);
                $vendor->user->transfer((int) ($vendorSubTotal), config('app.currency'));
            }else {
                $this->info('No orders to process.');
            }

            DB::commit();
        }catch(\Throwable $e) {
            DB::rollBack();
            $this->error($e->getMessage());
        }
    }
}
