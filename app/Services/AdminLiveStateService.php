<?php

namespace App\Services;

use App\Models\City;
use App\Models\Country;
use App\Models\Coupon;
use App\Models\CustomerSupport;
use App\Models\DispatchOrderItem;
use App\Models\Document;
use App\Models\Emergency;
use App\Models\ExpenseCard;
use App\Models\ExpenseSummary;
use App\Models\ExtraCharge;
use App\Models\KyoShinItem;
use App\Models\Order;
use App\Models\OsCashPayout;
use App\Models\OsMoneyTransfer;
use App\Models\Payment;
use App\Models\RiderRemit;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WalletHistory;
use App\Models\WithdrawRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminLiveStateService
{
    public function sidebarCounts(?User $user = null): array
    {
        $user = $user ?: auth()->user();
        if (! $user) {
            return [];
        }

        $hubService = app(DispatchHubService::class);
        $isHubUser = isDispatchHub($user);
        $workflow = app(DispatchOrderWorkflowService::class);

        $assign100 = (int) $hubService->poolCount($isHubUser ? (int) $user->id : null);
        $preOrder = (int) $workflow->preOrderCount();

        $assignedItems = (int) DispatchOrderItem::query()
            ->whereHas('messages', function ($q) {
                $q->where('sender_type', 'client')->whereNull('read_at');
            })
            ->count();

        $pickupError = (int) Order::query()
            ->where('status', 'pickup_error')
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNull('pickup_error_choice')
                    ->orWhereNotIn('pickup_error_choice', ['cancel', 'express']);
            })
            ->count();

        $pickupCancelled = (int) Order::query()
            ->where('status', 'cancelled')
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNotNull('pickup_error_at')
                    ->orWhereIn('pickup_error_choice', ['cancel', 'express'])
                    ->orWhere('reason', 'like', '%pickup error%')
                    ->orWhere('reason', 'like', '%User cancelled after pickup error%')
                    ->orWhere('reason', 'like', '%User chose express after pickup error%');
            })
            ->count();

        $hubInbox = $isHubUser ? (int) $hubService->inboxCount((int) $user->id) : 0;

        $hubInbound = [];
        if (! $isHubUser) {
            foreach ($hubService->accounts() as $hubAccount) {
                $hubInbound[(string) $hubAccount->id] = (int) $hubService->inboundCount((int) $hubAccount->id);
            }
        }

        $clientPendingOrders = 0;
        $clientWithdraw = 0;
        if (($user->user_type ?? '') === 'client') {
            $clientPendingOrders = (int) Order::query()
                ->where('client_id', $user->id)
                ->where('status', 'create')
                ->count();
            $clientWithdraw = (int) WithdrawRequest::query()
                ->where('user_id', $user->id)
                ->where('status', 'requested')
                ->count();
        }

        return [
            'assign100' => $assign100,
            'preOrder' => $preOrder,
            'assignedItems' => $assignedItems,
            'pickupError' => $pickupError,
            'pickupCancelled' => $pickupCancelled,
            'hubInbox' => $hubInbox,
            'hubInbound' => $hubInbound,
            'clientPendingOrders' => $clientPendingOrders,
            'clientWithdraw' => $clientWithdraw,
            'requestCount' => $clientPendingOrders,
        ];
    }

    public function notificationUnread(?User $user = null): int
    {
        $user = $user ?: auth()->user();
        if (! $user) {
            return 0;
        }

        syncUnreadDispatchItemMessageNotifications($user);
        $user->unsetRelation('unreadNotifications');

        return (int) ($user->unreadNotifications?->count() ?? 0);
    }

    public function dashboardStats(Request $request): array
    {
        $params = [
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
            'city_id' => $request->input('city_id'),
            'country_id' => $request->input('country_id'),
        ];

        $ordersQuery = Order::myOrder();
        if ($params['from_date'] && $params['to_date']) {
            if ($params['from_date'] == $params['to_date']) {
                $ordersQuery->whereDate('created_at', $params['from_date']);
            } else {
                $ordersQuery->whereBetween('created_at', [$params['from_date'], $params['to_date']]);
            }
        }
        if (! empty($params['country_id'])) {
            $ordersQuery->where('country_id', $params['country_id']);
        }
        if (! empty($params['city_id'])) {
            $ordersQuery->where('city_id', $params['city_id']);
        }

        $statuses = ['courier_assigned', 'active', 'courier_arrived', 'courier_departed', 'courier_picked_up'];

        return [
            'total_order_today' => (int) Order::myOrder()->today()->count(),
            'total_order_today_peding' => (int) Order::myOrder()->today()->where('status', 'create')->count(),
            'total_order_today_inprogress' => (int) Order::myOrder()->today()->whereIn('status', $statuses)->count(),
            'total_order_today_completed' => (int) Order::myOrder()->today()->where('status', 'completed')->count(),
            'total_order_today_cancelled' => (int) Order::myOrder()->today()->where('status', 'cancelled')->count(),
            'total_order' => (int) (clone $ordersQuery)->count(),
            'total_create_order' => (int) (clone $ordersQuery)->where('status', 'create')->count(),
            'total_accepetd_order' => (int) (clone $ordersQuery)->where('status', 'active')->count(),
            'total_assigned_order' => (int) (clone $ordersQuery)->where('status', 'courier_assigned')->count(),
            'total_arrived_order' => (int) (clone $ordersQuery)->where('status', 'courier_arrived')->count(),
            'total_pickup_order' => (int) (clone $ordersQuery)->where('status', 'courier_picked_up')->count(),
            'total_departed_order' => (int) (clone $ordersQuery)->where('status', 'courier_departed')->count(),
            'total_delivered_order' => (int) (clone $ordersQuery)->where('status', 'completed')->count(),
            'total_cancelled_order' => (int) (clone $ordersQuery)->where('status', 'cancelled')->count(),
        ];
    }

    public function pageVersion(string $pageKey, ?User $user = null, array $context = []): string
    {
        $user = $user ?: auth()->user();
        $cacheKey = 'admin_live_page_version:' . $pageKey . ':' . md5(json_encode($context) . ':' . ($user?->id ?? 0));

        return (string) cache()->remember($cacheKey, 2, function () use ($pageKey, $user, $context) {
            return $this->computePageVersion($pageKey, $user, $context);
        });
    }

    protected function computePageVersion(string $pageKey, ?User $user, array $context): string
    {
        $pageKey = strtolower(trim($pageKey));

        return match (true) {
            $pageKey === 'home' || $pageKey === 'dashboard' => $this->fingerprintOrders(),
            $pageKey === 'order.index' || $pageKey === 'order' || str_starts_with($pageKey, 'order.') && ! str_contains($pageKey, 'dispatch') => function_exists('adminOrderListLiveVersion')
                ? adminOrderListLiveVersion()
                : $this->fingerprintOrders(),
            $pageKey === 'order.dispatch.items' || ($pageKey === 'order.show' && ! empty($context['order_id'])) => function_exists('adminOrderItemsLiveVersion')
                ? adminOrderItemsLiveVersion((int) ($context['order_id'] ?? 0))
                : $this->fingerprintTable('orders', ['id' => (int) ($context['order_id'] ?? 0)]),
            str_contains($pageKey, 'assign-100') || $pageKey === 'assign100' => $this->fingerprintDispatchItems(),
            str_contains($pageKey, 'to-assign') || str_contains($pageKey, 'follow') => $this->fingerprintDispatchItems(),
            str_contains($pageKey, 'assigned-items') => $this->fingerprintDispatchItems() . '|' . $this->fingerprintMessages(),
            str_contains($pageKey, 'rider-list') || str_contains($pageKey, 'rider-items') => $this->fingerprintUsers('delivery_man') . '|' . $this->fingerprintDispatchItems(),
            str_contains($pageKey, 'os-list') || str_contains($pageKey, 'os-items') || $pageKey === 'users.index' || str_starts_with($pageKey, 'users.') => $this->fingerprintUsers('client') . '|' . $this->fingerprintDispatchItems(),
            str_contains($pageKey, 'deliveryman') => $this->fingerprintUsers('delivery_man'),
            str_contains($pageKey, 'kyo-shin') => $this->fingerprintModel(KyoShinItem::class),
            str_contains($pageKey, 'money-transfer') => $this->fingerprintModel(OsMoneyTransfer::class),
            str_contains($pageKey, 'cash-payout') => $this->fingerprintModel(OsCashPayout::class),
            str_contains($pageKey, 'rider-remit') => $this->fingerprintModel(RiderRemit::class),
            str_contains($pageKey, 'expense-summary') => $this->fingerprintModel(ExpenseSummary::class),
            str_contains($pageKey, 'expense') => $this->fingerprintModel(ExpenseCard::class),
            str_contains($pageKey, 'os-receive') || str_contains($pageKey, 'daily-check') => $this->fingerprintOrders() . '|' . $this->fingerprintDispatchItems(),
            str_contains($pageKey, 'notification') => $this->fingerprintNotifications($user),
            str_contains($pageKey, 'customer-suport') || str_contains($pageKey, 'customer_support') || str_contains($pageKey, 'support') => $this->fingerprintModel(CustomerSupport::class),
            str_contains($pageKey, 'withdraw') => $this->fingerprintModel(WithdrawRequest::class),
            str_contains($pageKey, 'wallet') => $this->fingerprintModel(WalletHistory::class),
            str_contains($pageKey, 'payment') => $this->fingerprintModel(Payment::class),
            str_contains($pageKey, 'coupon') => $this->fingerprintModel(Coupon::class),
            str_contains($pageKey, 'city') => $this->fingerprintModel(City::class),
            str_contains($pageKey, 'country') => $this->fingerprintModel(Country::class),
            str_contains($pageKey, 'vehicle') => $this->fingerprintModel(Vehicle::class),
            str_contains($pageKey, 'document') => $this->fingerprintModel(Document::class),
            str_contains($pageKey, 'extracharge') || str_contains($pageKey, 'extra-charge') => $this->fingerprintModel(ExtraCharge::class),
            str_contains($pageKey, 'emergency') => $this->fingerprintModel(Emergency::class),
            str_contains($pageKey, 'from-mdy') || str_contains($pageKey, 'from-hub') => $this->fingerprintDispatchItems(),
            default => $this->fingerprintOrders() . '|' . $this->fingerprintDispatchItems() . '|' . $this->fingerprintUsers(),
        };
    }

    protected function fingerprintOrders(): string
    {
        return $this->sha([
            Order::query()->count(),
            Order::query()->max('id'),
            Order::query()->max('updated_at'),
            Order::query()->max('status'),
        ]);
    }

    protected function fingerprintDispatchItems(): string
    {
        return $this->sha([
            DispatchOrderItem::query()->count(),
            DispatchOrderItem::query()->max('id'),
            DispatchOrderItem::query()->max('updated_at'),
            DispatchOrderItem::query()->selectRaw("COALESCE(SUM(LENGTH(COALESCE(status,''))),0) as fp")->value('fp'),
        ]);
    }

    protected function fingerprintMessages(): string
    {
        $table = Schema::hasTable('dispatch_item_messages')
            ? 'dispatch_item_messages'
            : (Schema::hasTable('dispatch_order_item_messages') ? 'dispatch_order_item_messages' : null);
        if (! $table) {
            return '0';
        }

        $row = DB::table($table)
            ->selectRaw('COUNT(*) as c, MAX(id) as mid, MAX(updated_at) as u, SUM(CASE WHEN read_at IS NULL THEN 1 ELSE 0 END) as unread')
            ->first();

        return $this->sha([$row->c ?? 0, $row->mid ?? 0, $row->u ?? '', $row->unread ?? 0]);
    }

    protected function fingerprintUsers(?string $userType = null): string
    {
        $q = User::query();
        if ($userType) {
            $q->where('user_type', $userType);
        }

        return $this->sha([
            (clone $q)->count(),
            (clone $q)->max('id'),
            (clone $q)->max('updated_at'),
            (clone $q)->max('approval_status'),
        ]);
    }

    protected function fingerprintNotifications(?User $user): string
    {
        if (! $user) {
            return '0';
        }

        return $this->sha([
            $user->notifications()->count(),
            $user->unreadNotifications()->count(),
            $user->notifications()->max('created_at'),
        ]);
    }

    protected function fingerprintModel(string $modelClass): string
    {
        if (! class_exists($modelClass)) {
            return '0';
        }

        try {
            $q = $modelClass::query();

            return $this->sha([
                (clone $q)->count(),
                (clone $q)->max('id'),
                (clone $q)->max('updated_at'),
            ]);
        } catch (\Throwable $e) {
            return '0';
        }
    }

    protected function fingerprintTable(string $table, array $where = []): string
    {
        if (! Schema::hasTable($table)) {
            return '0';
        }

        $q = DB::table($table);
        foreach ($where as $col => $val) {
            $q->where($col, $val);
        }

        return $this->sha([
            (clone $q)->count(),
            (clone $q)->max('id'),
            (clone $q)->max('updated_at'),
        ]);
    }

    protected function sha(array $parts): string
    {
        return sha1(implode('|', array_map(static fn ($p) => (string) $p, $parts)));
    }
}
