<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\User;
use App\Services\DispatchHubService;
use Illuminate\Support\Facades\Schema;

/**
 * Network control payload for Super Admin embedded screen.
 */
class NetworkControlService
{
    /**
     * @return array{
     *   missingAdmins: list<array{id:int,name:string}>,
     *   settlementCounts: array<string,int>,
     *   hubs: list<array{id:int,name:string,children:int}>,
     *   modeRows: list<array{id:int,name:string,mode:string,label:string,admin:?string,riders:int}>
     * }
     */
    public function payload(): array
    {
        $branches = Branch::query()
            ->whereNull('deleted_at')
            ->orderByRaw(destinationBranchOrderSql())
            ->orderBy('name')
            ->get();

        $adminsByBranch = User::query()
            ->where('user_type', 'admin')
            ->whereNull('deleted_at')
            ->whereNotNull('branch_id')
            ->where('branch_id', '>', 0)
            ->get(['id', 'name', 'branch_id'])
            ->keyBy('branch_id');

        $riderCounts = User::query()
            ->where('user_type', 'delivery_man')
            ->whereNull('deleted_at')
            ->whereNotNull('branch_id')
            ->selectRaw('branch_id, COUNT(*) as total')
            ->groupBy('branch_id')
            ->pluck('total', 'branch_id');

        $missingAdmins = [];
        $settlementCounts = [
            Branch::SETTLEMENT_MANUAL => 0,
            Branch::SETTLEMENT_MANUAL_HALF_DELI => 0,
            Branch::SETTLEMENT_HALF_DELI => 0,
        ];
        $modeRows = [];

        foreach ($branches as $branch) {
            if ((int) $branch->status !== 1) {
                continue;
            }
            $mode = method_exists($branch, 'settlementMode')
                ? $branch->settlementMode()
                : Branch::SETTLEMENT_MANUAL;
            $settlementCounts[$mode] = ($settlementCounts[$mode] ?? 0) + 1;
            $admin = $adminsByBranch->get($branch->id);
            if (! $admin) {
                $missingAdmins[] = [
                    'id' => (int) $branch->id,
                    'name' => (string) $branch->name,
                ];
            }
            $modeRows[] = [
                'id' => (int) $branch->id,
                'name' => (string) $branch->name,
                'mode' => $mode,
                'label' => match ($mode) {
                    Branch::SETTLEMENT_HALF_DELI => __('message.branch_settlement_half_deli'),
                    Branch::SETTLEMENT_MANUAL_HALF_DELI => __('message.branch_settlement_manual_half_deli'),
                    default => __('message.branch_settlement_manual'),
                },
                'admin' => $admin?->name,
                'riders' => (int) ($riderCounts[$branch->id] ?? 0),
            ];
        }

        $hubs = [];
        if (Schema::hasColumn('users', 'is_dispatch_hub')) {
            $hubUsers = User::query()
                ->where('user_type', 'delivery_man')
                ->where('status', 1)
                ->where('is_dispatch_hub', 1)
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get(['id', 'name']);
            foreach ($hubUsers as $hub) {
                $children = 0;
                if (Schema::hasColumn('users', 'hub_parent_id')) {
                    $children = User::query()
                        ->where('hub_parent_id', $hub->id)
                        ->whereNull('deleted_at')
                        ->count();
                }
                $hubs[] = [
                    'id' => (int) $hub->id,
                    'name' => (string) $hub->name,
                    'children' => $children,
                ];
            }
        }

        return [
            'missingAdmins' => $missingAdmins,
            'settlementCounts' => $settlementCounts,
            'hubs' => $hubs,
            'modeRows' => $modeRows,
            'defaultFuel' => app(\App\Services\RiderRemitService::class)->defaultFuelAmount(),
            'defaultOfficeSalary' => app(\App\Services\HrPayrollService::class)->defaultOfficeMonthlySalary(),
            'yangonBranchId' => (int) (app(DispatchHubService::class)->yangonBranchId() ?? 0),
            'mdyBranchId' => (int) (mandalayBranchId() ?? 0),
        ];
    }
}
