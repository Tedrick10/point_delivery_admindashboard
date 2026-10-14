<?php

namespace App\Console\Commands;

use App\Services\ExpenseRiderFuelSyncService;
use App\Services\RiderRemitService;
use Illuminate\Console\Command;

class ResyncRiderRemitFuelCommand extends Command
{
    protected $signature = 'rider-remit:resync-fuel {--day= : Optional remit day Y-m-d (default: all open remits)}';

    protected $description = 'Re-apply ဆီဖိုး rules on open remits and sync Expense Rider fuel (drops foreign-rider phantom fuel).';

    public function handle(RiderRemitService $remitService, ExpenseRiderFuelSyncService $fuelSync): int
    {
        $day = $this->option('day');
        if ($day) {
            $updated = $remitService->ensureOpenRemitDefaults((string) $day, null, null);
            $fuelSync->syncExpenseDate((string) $day, null, null);
            $this->info("Resynced {$day}: {$updated} remit row(s) updated.");

            return self::SUCCESS;
        }

        $updated = $remitService->resyncOpenRemitFuel(null);
        $this->info("Resynced open remits: {$updated} row(s) updated.");

        return self::SUCCESS;
    }
}
