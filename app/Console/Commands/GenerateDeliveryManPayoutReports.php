<?php

namespace App\Console\Commands;

use App\Models\DeliveryManPayoutReport;
use App\Models\Payment;
use App\Models\Wallet;
use App\Models\WalletHistory;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class GenerateDeliveryManPayoutReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payout:generate-delivery-man-reports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate weekly delivery man payout reports';

    /**
     * Execute the console command.
     *
     * @return int
     */
   public function handle()
    {
        $startDate = Carbon::now()->subWeek()->startOfWeek();
        $endDate   = Carbon::now()->subWeek()->endOfWeek();

        $drivers = Payment::where('is_settled', 0)->where('payment_type', 'wallet')->whereNotNull('delivery_man_id')->select('delivery_man_id')->distinct()->get();

        foreach ($drivers as $driver) {
            $earnings = Payment::where('delivery_man_id', $driver->delivery_man_id)->where('is_settled', 0)->get();
            if ($earnings->count() == 0) {
                continue;
            }

            $totalFare       = $earnings->sum('total_amount');
            $totalCommission = $earnings->sum('admin_commission');
            $totalEarnings   = $earnings->sum('delivery_man_fee');
            $totalTips       = $earnings->sum('delivery_man_tip');
            $totalTrips      = $earnings->count();
            $finalTotal      = $totalEarnings + $totalTips;

            try {

                $report = DeliveryManPayoutReport::create([
                    'delivery_man_id'  => $driver->delivery_man_id,
                    'week_start_date'  => $startDate,
                    'week_end_date'    => $endDate,
                    'total_trips'      => $totalTrips,
                    'total_fare'       => $totalFare,
                    'total_commission' => $totalCommission,
                    'driver_tips'      => $totalTips,
                    'payout_amount'    => $finalTotal,
                    'status'           => 'pending',
                    'generated_at'     => now(),
                ]);

                Payment::where('delivery_man_id', $driver->delivery_man_id)->where('is_settled', 0)->update([ 'is_settled' => true, 'payout_report_id' => $report->id ]);

                $currency_code = SettingData('CURRENCY', 'CURRENCY_CODE') ?? 'USD';
                $currency_data = currencyArray($currency_code);
                $currency = strtolower($currency_data['code']);

                $wallet = Wallet::firstOrCreate([ 'user_id' => $driver->delivery_man_id ]);

                $total_amount = $wallet->total_amount - $totalCommission;

                $wallet->update(['total_amount' => $total_amount ]);

                WalletHistory::create([
                    'user_id'     => $driver->delivery_man_id,
                    'type'        => 'debit',
                    'transaction_type' => __('message.delivery_man_payoutreport'),
                    'currency'    => $currency,
                    'amount'      => $totalCommission,
                    'balance'     => $total_amount,
                    'datetime'    => now(),
                    'week_date'   => $startDate.' '.$endDate
                ]);

                $this->info("Payout report generated for driver ID: {$driver->delivery_man_id}");

            } catch (\Exception $e) {

                $this->error("Failed for driver {$driver->delivery_man_id}: ".$e->getMessage());
            }
        }

        $this->info('Running payout report mail cron...');
        Artisan::call('app:send-driver-payout-report-mail');

        return Command::SUCCESS;
    }

}
