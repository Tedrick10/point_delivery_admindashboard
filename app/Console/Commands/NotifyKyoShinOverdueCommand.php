<?php

namespace App\Console\Commands;

use App\Services\KyoShinService;
use Illuminate\Console\Command;

class NotifyKyoShinOverdueCommand extends Command
{
    protected $signature = 'kyo-shin:notify-overdue';

    protected $description = 'Notify OS and Admin daily when ကြိုရှင်း parcels pass their last-Finished date';

    public function handle(KyoShinService $service): int
    {
        $sent = $service->notifyOverdue();

        $this->info("Sent {$sent} ကြိုရှင်း overdue notification(s).");

        return self::SUCCESS;
    }
}
