<?php

namespace App\Console\Commands;

use App\Services\KyoShinService;
use Illuminate\Console\Command;

class RecordKyoShinDailyLedgerCommand extends Command
{
    protected $signature = 'kyo-shin:record-daily-ledger';

    protected $description = 'Snapshot ကြိုရှင်း daily in/out ledger for every branch';

    public function handle(KyoShinService $service): int
    {
        $count = $service->recordDailyLedgers();

        $this->info("Recorded {$count} ကြိုရှင်း daily ledger row(s).");

        return self::SUCCESS;
    }
}
