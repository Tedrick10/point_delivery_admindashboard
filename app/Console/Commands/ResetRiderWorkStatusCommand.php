<?php

namespace App\Console\Commands;

use App\Services\RiderWorkStatusService;
use Illuminate\Console\Command;

class ResetRiderWorkStatusCommand extends Command
{
    protected $signature = 'riders:reset-work-status';

    protected $description = 'Turn yesterday\'s Off riders/employees back to On at the start of a new day';

    public function handle(RiderWorkStatusService $service): int
    {
        $today = now('Asia/Yangon')->toDateString();
        $updated = $service->resetQuery($today);

        $this->info("Reset {$updated} rider(s) to On.");

        return self::SUCCESS;
    }
}
