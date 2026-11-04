<?php

namespace App\Console\Commands;

use App\Services\DispatchOrderWorkflowService;
use Illuminate\Console\Command;

class ReleaseCancelledRiderAssignmentsCommand extends Command
{
    protected $signature = 'dispatch:release-cancelled-rider-assignments {--order_id= : Limit to one cancelled order id}';

    protected $description = 'Release stale Rider App Assigned/On Way/Pending links still tied to cancelled parent orders';

    public function handle(DispatchOrderWorkflowService $workflow): int
    {
        $orderId = $this->option('order_id');
        $orderId = $orderId !== null && $orderId !== '' ? (int) $orderId : null;

        $released = $workflow->releaseStaleCancelledRiderAssignments($orderId);

        $this->info('Released '.$released.' dispatch item(s) from cancelled orders.');

        return Command::SUCCESS;
    }
}
