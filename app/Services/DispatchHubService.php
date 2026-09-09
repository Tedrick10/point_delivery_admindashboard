<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use App\Models\Role;
use Spatie\Permission\Models\Permission;

class DispatchHubService
{
    public const HUB_EMAILS = [
        'rider.ygn1@demo.local',
        'rider.ygn2@demo.local',
    ];

    public const HUB_PERMISSIONS = [
        'order-list',
        'order-show',
        'order-add',
        'order-edit',
        'deliveryman-list',
        'deliveryman-show',
        'deliveryman-add',
        'deliveryman-edit',
        'users-list',
        'users-add',
        'users-edit',
        'users-show',
    ];

    public function isHub(?User $user): bool
    {
        return $user !== null && (int) ($user->is_dispatch_hub ?? 0) === 1;
    }

    public function accounts(): Collection
    {
        if (! Schema::hasColumn('users', 'is_dispatch_hub')) {
            return collect();
        }

        return User::query()
            ->where('user_type', 'delivery_man')
            ->where('is_dispatch_hub', 1)
            ->where('status', 1)
            ->orderByRaw("FIELD(email, '".implode("','", self::HUB_EMAILS)."')")
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'branch_id']);
    }

    public function findHub(int $hubId): ?User
    {
        if ($hubId <= 0 || ! Schema::hasColumn('users', 'is_dispatch_hub')) {
            return null;
        }

        return User::query()
            ->where('id', $hubId)
            ->where('user_type', 'delivery_man')
            ->where('is_dispatch_hub', 1)
            ->where('status', 1)
            ->first();
    }

    public function yangonBranchId(): ?int
    {
        static $id = false;
        if ($id !== false) {
            return $id;
        }

        $found = (int) (\App\Models\Branch::query()
            ->where('status', 1)
            ->where('name', 'ရန်ကုန်')
            ->value('id') ?? 0);

        $id = $found > 0 ? $found : null;

        return $id;
    }

    public function ensureAccess(User $user): void
    {
        if (! $this->isHub($user)) {
            return;
        }

        if (! class_exists(Role::class)) {
            return;
        }

        $role = Role::firstOrCreate(
            ['name' => 'dispatch_hub', 'guard_name' => 'web'],
            ['status' => 1]
        );

        $adminRole = Role::query()->where('name', 'admin')->where('guard_name', 'web')->first();
        if ($adminRole && method_exists($adminRole, 'permissions')) {
            $role->syncPermissions($adminRole->permissions);
        } else {
            $permissionIds = Permission::query()
                ->whereIn('name', self::HUB_PERMISSIONS)
                ->pluck('id');
            if ($permissionIds->isNotEmpty()) {
                $role->syncPermissions($permissionIds);
            }
        }

        if (! $user->hasRole('dispatch_hub')) {
            $user->assignRole($role);
        }

        try {
            app('cache')->forget(config('permission.cache.key'));
        } catch (\Throwable $e) {
            // Permission cache reset is best-effort.
        }
    }

    public function originHubIdForOrder(?\App\Models\Order $order, ?User $actor = null): ?int
    {
        if (! $order) {
            return $actor && $this->isHub($actor) ? (int) $actor->id : null;
        }

        $pickup = $order->pickup_point;
        if (! is_array($pickup)) {
            $pickup = json_decode((string) $pickup, true) ?: [];
        }
        $stamped = (int) ($pickup['hub_user_id'] ?? 0);
        if ($stamped > 0 && $this->findHub($stamped)) {
            return $stamped;
        }

        $riderId = (int) ($order->delivery_man_id ?? 0);
        if ($riderId > 0) {
            $rider = User::query()->find($riderId);
            if ($rider && $this->isHub($rider)) {
                return (int) $rider->id;
            }
            $parent = (int) ($rider->hub_parent_id ?? 0);
            if ($parent > 0 && $this->findHub($parent)) {
                return $parent;
            }
        }

        if ($actor && $this->isHub($actor)) {
            $yangonId = $this->yangonBranchId();
            $clientBranch = (int) ($order->client->branch_id ?? 0);
            $fromBranch = (int) ($pickup['from_branch_id'] ?? 0);
            if (($yangonId && ($clientBranch === (int) $yangonId || $fromBranch === (int) $yangonId))
                || $clientBranch === (int) $actor->branch_id
            ) {
                return (int) $actor->id;
            }
        }

        return null;
    }

    public function stampOrderOrigin(\App\Models\Order $order, ?User $actor = null): void
    {
        $hubId = $this->originHubIdForOrder($order, $actor);
        if (! $hubId) {
            return;
        }

        $pickup = $order->pickup_point;
        if (! is_array($pickup)) {
            $pickup = json_decode((string) $pickup, true) ?: [];
        }
        $pickup['hub_user_id'] = $hubId;
        $yangonId = $this->yangonBranchId();
        if ($yangonId && empty($pickup['from_branch_id'])) {
            $pickup['from_branch_id'] = $yangonId;
        }
        $order->pickup_point = $pickup;
        $order->save();
    }

    public function claimLocalOriginItem(\App\Models\DispatchOrderItem $item, ?\App\Models\Order $order = null, ?User $actor = null): bool
    {
        if ((int) ($item->hub_user_id ?? 0) > 0) {
            return false;
        }

        $order = $order ?? $item->order;
        $hubId = $this->originHubIdForOrder($order, $actor);
        if (! $hubId) {
            return false;
        }

        $item->hub_user_id = $hubId;
        $item->hub_inbox_at = $item->hub_inbox_at ?? now();
        $item->hub_accepted_at = $item->hub_accepted_at ?? now();

        $yangonId = $this->yangonBranchId();
        if ($yangonId && (int) ($item->from_branch_id ?? 0) <= 0) {
            $item->from_branch_id = $yangonId;
        }

        return true;
    }

    public function claimLocalOriginItemsForHub(User $hub): int
    {
        if (! $this->isHub($hub) || ! Schema::hasColumn('dispatch_order_items', 'hub_user_id')) {
            return 0;
        }

        $hubId = (int) $hub->id;
        $childIds = User::query()
            ->where('hub_parent_id', $hubId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $riderIds = array_values(array_unique(array_merge([$hubId], $childIds)));

        $items = \App\Models\DispatchOrderItem::query()
            ->where('status', 'assigned')
            ->whereNull('hub_user_id')
            ->whereHas('order', function ($query) use ($hubId, $riderIds) {
                $query->where(function ($inner) use ($hubId, $riderIds) {
                    $inner->whereIn('delivery_man_id', $riderIds ?: [0])
                        ->orWhere('pickup_point->hub_user_id', $hubId)
                        ->orWhere('pickup_point', 'like', '%"hub_user_id":'.$hubId.'%')
                        ->orWhere('pickup_point', 'like', '%"hub_user_id": '.$hubId.'%');
                });
            })
            ->with('order')
            ->get();

        $claimed = 0;
        foreach ($items as $item) {
            if ($this->claimLocalOriginItem($item, $item->order, $hub)) {
                $item->save();
                $claimed++;
            }
        }

        return $claimed;
    }

    public function inboundMenuLabel(User $hub): string
    {
        $isM2m = str_contains(strtolower((string) $hub->email), 'ygn2')
            || str_contains((string) $hub->name, 'M2M');

        return __('message.from_hub_to_mdy', [
            'hub' => $isM2m ? 'M2M' : $hub->name,
        ]);
    }

    public function applyMdyPool($query)
    {
        if (! Schema::hasColumn('dispatch_order_items', 'hub_user_id')) {
            return $query;
        }

        $query->whereNull('hub_user_id');
        if (Schema::hasColumn('dispatch_order_items', 'mdy_inbox_at')) {
            $query->whereNull('mdy_inbox_at');
        }

        return $query;
    }

    public function applyNotSentToMdy($query)
    {
        if (Schema::hasColumn('dispatch_order_items', 'mdy_inbox_at')) {
            $query->whereNull('mdy_inbox_at');
        }

        return $query;
    }

    public function applyHubInbox($query, int $hubId)
    {
        if (! Schema::hasColumn('dispatch_order_items', 'hub_user_id')) {
            return $query->whereRaw('0 = 1');
        }

        return $this->applyNotSentToMdy(
            $query->where('hub_user_id', $hubId)->whereNull('hub_accepted_at')
        );
    }

    public function applyHubPool($query, int $hubId)
    {
        if (! Schema::hasColumn('dispatch_order_items', 'hub_user_id')) {
            return $query->whereRaw('0 = 1');
        }

        return $this->applyNotSentToMdy(
            $query->where('hub_user_id', $hubId)->whereNotNull('hub_accepted_at')
        );
    }

    public function applyHubQueue($query, int $hubId)
    {
        if (! Schema::hasColumn('dispatch_order_items', 'hub_user_id')) {
            return $query->whereRaw('0 = 1');
        }

        return $this->applyNotSentToMdy($query->where('hub_user_id', $hubId));
    }

    public function applyMdyInbound($query, int $hubId)
    {
        if (! Schema::hasColumn('dispatch_order_items', 'hub_user_id')
            || ! Schema::hasColumn('dispatch_order_items', 'mdy_inbox_at')
        ) {
            return $query->whereRaw('0 = 1');
        }

        return $query
            ->where('hub_user_id', $hubId)
            ->whereNotNull('mdy_inbox_at')
            ->whereNull('mdy_accepted_at');
    }

    public function inboxCount(int $hubId): int
    {
        if ($hubId <= 0 || ! Schema::hasColumn('dispatch_order_items', 'hub_user_id')) {
            return 0;
        }

        $query = \App\Models\DispatchOrderItem::query()
            ->where('status', 'assigned');
        $this->applyHubInbox($query, $hubId);

        return (int) $query->count();
    }

    public function queueCount(int $hubId): int
    {
        if ($hubId <= 0 || ! Schema::hasColumn('dispatch_order_items', 'hub_user_id')) {
            return 0;
        }

        $query = \App\Models\DispatchOrderItem::query()
            ->where('status', 'assigned');
        $this->applyHubQueue($query, $hubId);

        return (int) $query->count();
    }

    public function inboundCount(int $hubId): int
    {
        if ($hubId <= 0 || ! Schema::hasColumn('dispatch_order_items', 'mdy_inbox_at')) {
            return 0;
        }

        $query = \App\Models\DispatchOrderItem::query()
            ->where('status', 'assigned');
        $this->applyMdyInbound($query, $hubId);

        return (int) $query->count();
    }

    public function poolCount(?int $hubId = null): int
    {
        $query = \App\Models\DispatchOrderItem::query()
            ->where('status', 'assigned')
            ->whereHas('order', function ($q) {
                $q->whereIn('status', ['courier_picked_up', 'courier_departed', 'completed']);
            });

        if ($hubId) {
            $this->applyHubPool($query, $hubId);
        } else {
            $this->applyMdyPool($query);
        }

        return (int) $query->count();
    }
}
