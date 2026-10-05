<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\DispatchOrderItem;
use App\Models\ExpenseCard;
use App\Models\ExpenseItem;
use App\Models\Order;
use App\Models\User;
use App\Services\ExpenseSummaryService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Generated Summary rows + Daily Check OS income for early October 2026.
 */
class ExpenseSummaryDemoSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::query()
            ->where('status', 1)
            ->where(function ($q) {
                $q->where('name', 'မန္တလေး')
                    ->orWhere('name', 'like', '%MDY%');
            })
            ->orderBy('id')
            ->first()
            ?? Branch::query()->where('status', 1)->orderBy('id')->first();

        if (! $branch) {
            $this->command?->warn('No branch for expense summary demo.');

            return;
        }

        $branchId = (int) $branch->id;
        $adminId = (int) (User::query()->whereIn('user_type', ['admin', 'super_admin'])->value('id') ?? 1);
        $src = DispatchOrderItem::query()->with('order')->whereNotNull('order_id')->orderByDesc('id')->first();
        $clients = User::query()->where('user_type', 'client')->where('status', 1)->orderBy('id')->limit(6)->get();
        $riders = User::query()->where('user_type', 'delivery_man')->where('status', 1)->orderBy('id')->limit(4)->get();

        $days = [
            '2026-10-01' => ['expense' => 60000, 'deli' => [3500, 2800, 4200, 2500]],
            '2026-10-02' => ['expense' => 18000, 'deli' => [3000, 2200, 4100]],
            '2026-10-03' => ['expense' => 24500, 'deli' => [2600, 3900, 1800, 3200]],
        ];

        $this->cleanupDemoItems();

        foreach ($days as $day => $spec) {
            $this->seedOsIncome($src, $clients, $riders, $branchId, $day, $spec['deli']);
            $card = $this->ensureExpenseCard($day, $branchId, $adminId, (float) $spec['expense']);
            app(ExpenseSummaryService::class)->generateFromCard($card, $adminId);
        }

        $this->command?->info('Expense Summary demo ready for 2026-10-01 .. 2026-10-03 ('.$branch->name.').');
    }

    protected function ensureExpenseCard(string $day, int $branchId, int $adminId, float $amount): ExpenseCard
    {
        $card = ExpenseCard::query()
            ->whereDate('expense_date', $day)
            ->where('branch_id', $branchId)
            ->first();

        if (! $card) {
            $card = ExpenseCard::query()->create([
                'expense_date' => $day,
                'branch_id' => $branchId,
                'total_amount' => $amount,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]);
        }

        if ($card->items()->exists()) {
            $card->recalculateTotal();

            return $card->fresh();
        }

        ExpenseItem::query()->create([
            'expense_card_id' => $card->id,
            'subject' => $day === '2026-10-01' ? 'Rider ဆီဖိုး' : 'Expense Summary demo',
            'amount' => $amount,
            'sort_order' => 1,
        ]);
        $card->recalculateTotal();

        return $card->fresh();
    }

    protected function seedOsIncome(
        ?DispatchOrderItem $src,
        $clients,
        $riders,
        int $branchId,
        string $listDay,
        array $deliValues
    ): void {
        if (! $src || ! $src->order || $clients->isEmpty() || $riders->isEmpty()) {
            return;
        }

        $completedAt = Carbon::parse($listDay, 'Asia/Yangon')->addDay()->setTime(10, 30, 0);

        foreach ($deliValues as $i => $deliAmount) {
            $client = $clients[$i % $clients->count()];
            $rider = $riders[$i % $riders->count()];
            $payAmount = 10000 + ($i * 1500);
            $payCalc = DispatchOrderItem::computeAmounts($payAmount, $deliAmount, 0, 0, 'customer');

            $order = Order::query()->where('client_id', $client->id)->orderByDesc('id')->first();
            if (! $order) {
                $order = $src->order->replicate();
                $order->client_id = $client->id;
                $order->save();
            }

            $item = $src->replicate([
                'code', 'admin_finished_at', 'photo_id', 'pending_photo_id',
                'delivered_photo_id', 'cust_photo_id', 'cust_sign_id',
            ]);
            $item->code = DispatchOrderItem::generateCode();
            $item->from_branch_id = $branchId;
            $item->to_branch_id = $branchId;
            $item->order_id = $order->id;
            $item->delivery_man_id = (int) $rider->id;
            $item->os_paid = 0;
            $item->advance_paid = 0;
            $item->gate_amount = 0;
            $item->gate_os_paid = 0;
            $item->status = 'completed';
            $item->admin_completed_at = $completedAt;
            $item->admin_finished_at = $completedAt;
            $item->admin_updated_at = $completedAt;
            $item->received_date = $listDay;
            $item->assigned_at = $listDay.' 09:00:00';
            $item->item_name = 'Expense Summary demo '.$listDay.'-'.($i + 1);
            $item->remark = 'Expense Summary demo';
            $item->item_value = $payAmount;
            $item->deli_amount = $deliAmount;
            $item->credit_to = 'customer';
            $item->pickup_pay_mode = 'customer_pay';
            $item->cust_get = $payCalc['cust_get'];
            $item->os_to_pay = $payCalc['os_to_pay'];
            $item->customer_name = (string) $client->name;
            $item->save();
        }
    }

    protected function cleanupDemoItems(): void
    {
        DispatchOrderItem::query()
            ->where('remark', 'Expense Summary demo')
            ->orWhere('item_name', 'like', 'Expense Summary demo %')
            ->delete();
    }
}
