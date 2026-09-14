<?php

namespace App\Services;

use App\Models\DispatchOrderItem;
use App\Models\ExpenseCard;
use App\Models\ExpenseItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ExpenseAgentFeeSyncService
{
    public function subject(): string
    {
        return (string) __('message.expenses_agent_fee');
    }

    public function syncExpenseDate(string $expenseDay, ?int $userId = null, ?int $branchId = null): void
    {
        if (! Schema::hasColumn('dispatch_order_items', 'agent_amount')) {
            return;
        }

        $expenseDay = Carbon::parse($expenseDay)->toDateString();
        $this->repairAgentExpenseBranchesForDay($expenseDay);

        foreach ($this->branchIdsForExpenseDay($expenseDay, $branchId) as $id) {
            $this->upsertAgentFeeOnExpenseDay(
                $expenseDay,
                $this->agentTotalForExpenseDay($expenseDay, $id),
                $userId,
                $id
            );
        }
    }

    public function agentTotalForExpenseDay(string $expenseDay, ?int $branchId = null): float
    {
        if (! Schema::hasColumn('dispatch_order_items', 'agent_amount')) {
            return 0.0;
        }

        $day = Carbon::parse($expenseDay)->toDateString();
        $this->repairAgentExpenseBranchesForDay($day);

        return round((float) $this->intercityAgentItemsQuery($day)
            ->when($branchId && $branchId > 0, function ($q) use ($branchId) {
                $this->applyAgentBranchFilter($q, $branchId);
            })
            ->sum('agent_amount'), 2);
    }

    /**
     * @return list<int>
     */
    protected function branchIdsForExpenseDay(string $expenseDay, ?int $branchId = null): array
    {
        if ($branchId && $branchId > 0) {
            return [$branchId];
        }

        $tabIds = function_exists('destinationBranchTabs')
            ? collect(destinationBranchTabs()->pluck('id')->all())->map(fn ($id) => (int) $id)->filter()->values()
            : collect();

        $fromItems = collect(
            $this->intercityAgentItemsQuery($expenseDay)
                ->where(function ($q) {
                    $q->where('agent_expense_branch_id', '>', 0);
                    if (Schema::hasColumn('users', 'branch_id')) {
                        $q->orWhereHas('deliveryMan', fn ($u) => $u->where('branch_id', '>', 0));
                    }
                })
                ->with('deliveryMan:id,branch_id')
                ->get(['id', 'agent_expense_branch_id', 'delivery_man_id'])
                ->map(fn (DispatchOrderItem $item) => $this->resolveAgentExpenseBranchId($item))
                ->all()
        )->filter()->values();

        $fromCards = collect(
            ExpenseCard::query()
                ->whereDate('expense_date', $expenseDay)
                ->where('branch_id', '>', 0)
                ->pluck('branch_id')
                ->all()
        )->map(fn ($id) => (int) $id)->filter()->values();

        $ids = $fromItems->merge($fromCards)->unique()->filter()->values();
        if ($tabIds->isNotEmpty()) {
            $ids = $ids->intersect($tabIds->all())->values();
        }

        if ($ids->isEmpty()) {
            $default = function_exists('defaultDestinationBranchId') ? defaultDestinationBranchId() : null;
            if ($default) {
                return [(int) $default];
            }
        }

        return $ids->all();
    }

    protected function upsertAgentFeeOnExpenseDay(string $expenseDay, float $total, ?int $userId = null, ?int $branchId = null): void
    {
        $branchId = $branchId && $branchId > 0
            ? $branchId
            : (function_exists('defaultDestinationBranchId') ? defaultDestinationBranchId() : null);

        try {
            DB::transaction(function () use ($expenseDay, $total, $userId, $branchId) {
                $cardQuery = ExpenseCard::query()->whereDate('expense_date', $expenseDay);
                if ($branchId) {
                    $cardQuery->where('branch_id', $branchId);
                } else {
                    $cardQuery->whereNull('branch_id');
                }

                $card = $cardQuery->first();
                if (! $card) {
                    if ($total <= 0) {
                        return;
                    }
                    $card = ExpenseCard::query()->create([
                        'expense_date' => $expenseDay,
                        'branch_id' => $branchId,
                        'total_amount' => 0,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);
                }

                if ($card->isGenerated()) {
                    return;
                }

                $item = $this->findAgentFeeItem($card);
                $subject = $this->subject();

                if ($total <= 0) {
                    if ($item) {
                        $item->delete();
                    }
                    $card->forceFill(['updated_by' => $userId ?? $card->updated_by])->save();
                    $card->recalculateTotal();

                    return;
                }

                if ($item) {
                    $item->fill([
                        'subject' => $subject,
                        'amount' => $total,
                        'source' => ExpenseItem::SOURCE_AGENT_FEE,
                        'sort_order' => 1,
                    ])->save();
                } else {
                    ExpenseItem::query()->create([
                        'expense_card_id' => $card->id,
                        'subject' => $subject,
                        'amount' => $total,
                        'sort_order' => 1,
                        'source' => ExpenseItem::SOURCE_AGENT_FEE,
                    ]);
                }

                $card->forceFill(['updated_by' => $userId ?? $card->updated_by])->save();
                $card->recalculateTotal();
            });
        } catch (\Throwable $e) {
            Log::warning('expense agent fee sync failed', [
                'expense_day' => $expenseDay,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function findAgentFeeItem(ExpenseCard $card): ?ExpenseItem
    {
        $subject = $this->subject();

        return ExpenseItem::query()
            ->where('expense_card_id', $card->id)
            ->where(function ($q) use ($subject) {
                $q->where('source', ExpenseItem::SOURCE_AGENT_FEE)
                    ->orWhere('subject', $subject)
                    ->orWhere('subject', 'Agent ရငွေ')
                    ->orWhere('subject', 'Agent fee');
            })
            ->orderBy('id')
            ->first();
    }

    /**
     * Delivered proof photos for intercity Agent ရငွေ on an expense day.
     *
     * @return list<array{item_id:int,order_id:int,code:?string,point_amount:float,agent_amount:float,photo_url:string}>
     */
    public function deliveredProofsForExpenseDay(string $expenseDay, ?int $branchId = null): array
    {
        if (! Schema::hasColumn('dispatch_order_items', 'agent_amount')) {
            return [];
        }

        $day = Carbon::parse($expenseDay)->toDateString();
        $this->repairAgentExpenseBranchesForDay($day);

        $items = $this->intercityAgentItemsQuery($day)
            ->where('agent_amount', '>', 0)
            ->where('delivered_photo_id', '>', 0)
            ->when($branchId && $branchId > 0, function ($q) use ($branchId) {
                $this->applyAgentBranchFilter($q, $branchId);
            })
            ->with('deliveredPhotoMedia')
            ->orderBy('id')
            ->get();

        $seen = [];
        $rows = [];

        foreach ($items as $item) {
            $url = function_exists('dispatchItemProofPhotoUrl')
                ? dispatchItemProofPhotoUrl($item, 'delivered')
                : null;
            if (! $url) {
                continue;
            }

            $photoKey = (int) ($item->delivered_photo_id ?? 0) . '|' . $url;
            if (isset($seen[$photoKey])) {
                $idx = $seen[$photoKey];
                $rows[$idx]['point_amount'] = round($rows[$idx]['point_amount'] + (float) ($item->point_amount ?? 0), 2);
                $rows[$idx]['agent_amount'] = round($rows[$idx]['agent_amount'] + (float) ($item->agent_amount ?? 0), 2);
                continue;
            }

            $seen[$photoKey] = count($rows);
            $rows[] = [
                'item_id' => (int) $item->id,
                'order_id' => (int) ($item->order_id ?? 0),
                'code' => $item->code ?: null,
                'point_amount' => round((float) ($item->point_amount ?? 0), 2),
                'agent_amount' => round((float) ($item->agent_amount ?? 0), 2),
                'photo_url' => $url,
            ];
        }

        return $rows;
    }

    /**
     * Fix rows that stored MDY/YGN panel id instead of the rider's destination branch.
     */
    protected function repairAgentExpenseBranchesForDay(string $expenseDay): void
    {
        if (! Schema::hasColumn('dispatch_order_items', 'agent_expense_branch_id')) {
            return;
        }

        $items = $this->intercityAgentItemsQuery($expenseDay)
            ->with('deliveryMan:id,branch_id')
            ->get(['id', 'agent_expense_branch_id', 'delivery_man_id']);

        foreach ($items as $item) {
            $correct = $this->resolveAgentExpenseBranchId($item);
            if (! $correct) {
                continue;
            }
            if ((int) ($item->agent_expense_branch_id ?? 0) === $correct) {
                continue;
            }
            $item->forceFill(['agent_expense_branch_id' => $correct])->save();
        }
    }

    protected function resolveAgentExpenseBranchId(DispatchOrderItem $item): ?int
    {
        $riderBranch = (int) (optional($item->deliveryMan)->branch_id ?? 0);
        if ($riderBranch > 0 && function_exists('isOtherDestinationBranch') && isOtherDestinationBranch($riderBranch)) {
            return $riderBranch;
        }

        $stored = (int) ($item->agent_expense_branch_id ?? 0);
        if ($stored > 0 && function_exists('isOtherDestinationBranch') && isOtherDestinationBranch($stored)) {
            return $stored;
        }

        if ($riderBranch > 0) {
            return $riderBranch;
        }

        return $stored > 0 ? $stored : null;
    }

    protected function intercityAgentItemsQuery(string $day)
    {
        return DispatchOrderItem::query()
            ->where('status', 'completed')
            ->where('delivered_type', 'intercity')
            ->where(function ($q) use ($day) {
                $q->whereDate('rider_remit_date', $day)
                    ->orWhere(function ($inner) use ($day) {
                        $inner->whereNull('rider_remit_date')->whereDate('delivered_at', $day);
                    });
            });
    }

    protected function applyAgentBranchFilter($query, int $branchId): void
    {
        // Source of truth = rider's destination branch (not MDY/YGN panel).
        $query->where(function ($inner) use ($branchId) {
            $inner->whereHas('deliveryMan', fn ($u) => $u->where('branch_id', $branchId));
            $inner->orWhere(function ($fallback) use ($branchId) {
                $fallback->where('agent_expense_branch_id', $branchId)
                    ->where(function ($missingRider) {
                        $missingRider->whereNull('delivery_man_id')
                            ->orWhere('delivery_man_id', 0)
                            ->orWhereDoesntHave('deliveryMan');
                    });
            });
        });
    }
}
