<?php

namespace App\Console\Commands;

use App\Services\ExpenseRiderFuelSyncService;
use App\Services\RiderRemitService;
use Illuminate\Console\Command;

class ResyncRiderRemitFuelCommand extends Command
{
    protected $signature = 'rider-remit:resync-fuel {--day= : Optional remit day Y-m-d (default: all open remits)}';

    protected $description = 'Re-apply ဆီဖိုး / တန်ဆာခ rules on open remits and sync Expense Rider fuel (drops sticky phantom amounts).';

    public function handle(RiderRemitService $remitService, ExpenseRiderFuelSyncService $fuelSync): int
    {
        $day = $this->option('day');
        if ($day) {
            $defaults = $remitService->ensureOpenRemitDefaults((string) $day, null, null);
            $updated = $remitService->resyncOpenRemitFuel(null, (string) $day);
            $fuelSync->syncExpenseDate((string) $day, null, null);
            $this->info("Resynced {$day}: {$defaults} default row(s), {$updated} open remit fuel/fee row(s).");

            return self::SUCCESS;
        }

        $updated = $remitService->resyncOpenRemitFuel(null);
        $this->info("Resynced open remits: {$updated} row(s) updated.");

        return self::SUCCESS;
    }
}
