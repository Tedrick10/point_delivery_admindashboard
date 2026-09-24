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

    public const YANGON_BRANCH_NAMES = [
        'ရန်ကုန်',
        'Yangon',
        'Yangon Branch',
        'Yangon Ngwe Latt Saung',
        'Yangon M2M',
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

    public function isMdyReturn(?User $user): bool
    {
        return $user !== null && (int) ($user->is_mdy_return ?? 0) === 1;
    }

    public function mandalayBranchId(): ?int
    {
        if (function_exists('mandalayBranchId')) {
            return mandalayBranchId();
        }

        static $id = false;
        if ($id !== false) {
            return $id;
        }

        $found = (int) (\App\Models\Branch::query()
            ->where('status', 1)
            ->where('name', 'မန္တလေး')
            ->value('id') ?? 0);

        $id = $found > 0 ? $found : null;

        return $id;
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

    /**
     * @return list<int>
     */
    public function yangonBranchIds(): array
    {
        static $ids = null;
        if ($ids !== null) {
            return $ids;
        }

        $ids = \App\Models\Branch::query()
            ->where('status', 1)
            ->where(function ($query) {
                $query->whereIn('name', self::YANGON_BRANCH_NAMES)
                    ->orWhereIn('city_name', ['Yangon', 'ရန်ကုန်'])
                    ->orWhere('code', 'like', 'YGN%');
            })
            ->orderByRaw("FIELD(name, 'Yangon Ngwe Latt Saung','Yangon M2M','ရန်ကုန်','Yangon','Yangon Branch')")
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        return $ids;
    }

    public function yangonBranchId(): ?int
    {
        $ids = $this->yangonBranchIds();

        return $ids[0] ?? null;
    }

    public function isYangonBranch(?int $branchId): bool
    {
        $branchId = (int) $branchId;

        return $branchId > 0 && in_array($branchId, $this->yangonBranchIds(), true);
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

        $this->ensureMdyReturnDeliveryMan($user);
    }

    /**
     * Ensure each Yangon hub has a မန္တလေး (MDY) return rider for cross-hub deliver / assign.
     */
    public function ensureMdyReturnDeliveryMan(User $hub): ?User
    {
        if (! $this->isHub($hub)
            || ! Schema::hasColumn('users', 'hub_parent_id')
            || ! Schema::hasColumn('users', 'is_mdy_return')
        ) {
            return null;
        }

        $existing = User::withTrashed()
            ->where('user_type', 'delivery_man')
            ->where('hub_parent_id', (int) $hub->id)
            ->where(function ($q) {
                $q->where('is_mdy_return', 1);
                $mdyId = $this->mandalayBranchId();
                if ($mdyId) {
                    $q->orWhere('branch_id', (int) $mdyId);
                }
            })
            ->orderByDesc('is_mdy_return')
            ->orderBy('id')
            ->first();

        $mdyBranchId = (int) ($this->mandalayBranchId() ?? 0);
        $now = now();
        $emailLocal = 'rider.mdy.hub'.(int) $hub->id;
        $email = $emailLocal.'@demo.local';

        $payload = [
            'name' => 'မန္တလေး (MDY)',
            'username' => $emailLocal,
            'contact_number' => '+9592222'.str_pad((string) ((int) $hub->id % 10000), 4, '0', STR_PAD_LEFT),
            'user_type' => 'delivery_man',
            'status' => 1,
            'is_dispatch_hub' => 0,
            'is_mdy_return' => 1,
            'hub_parent_id' => (int) $hub->id,
            'branch_id' => $mdyBranchId > 0 ? $mdyBranchId : ((int) ($hub->branch_id ?? 0) ?: null),
            'country_id' => (int) ($hub->country_id ?? 1) ?: 1,
            'city_id' => (int) ($hub->city_id ?? 1) ?: 1,
            'rider_work_on' => true,
            'email_verified_at' => $now,
            'otp_verify_at' => $now,
            'document_verified_at' => $now,
            'is_autoverified_email' => 1,
            'is_autoverified_mobile' => 1,
            'is_autoverified_document' => 1,
            'deleted_at' => null,
        ];

        if ($existing) {
            $existing->fill($payload)->save();

            return $existing->fresh();
        }

        $byEmail = User::withTrashed()->where('email', $email)->first();
        if ($byEmail) {
            $byEmail->fill($payload)->save();

            return $byEmail->fresh();
        }

        $payload['email'] = $email;
        $payload['password'] = \Illuminate\Support\Facades\Hash::make('12345678');
        $user = User::create($payload);
        if ($user && method_exists($user, 'assignRole') && ! $user->hasRole('delivery_man')) {
            $user->assignRole('delivery_man');
        }

        return $user;
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
            $clientBranch = (int) ($order->client->branch_id ?? 0);
            $fromBranch = (int) ($pickup['from_branch_id'] ?? 0);
            if ($this->isYangonBranch($clientBranch)
                || $this->isYangonBranch($fromBranch)
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
        $hub = $this->findHub($hubId);
        $fromBranchId = (int) ($hub?->branch_id ?? 0) ?: (int) ($this->yangonBranchId() ?? 0);
        if ($fromBranchId > 0 && empty($pickup['from_branch_id'])) {
            $pickup['from_branch_id'] = $fromBranchId;
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

        $fromBranchId = (int) ($actor?->branch_id ?? 0) ?: (int) ($this->yangonBranchId() ?? 0);
        if ($fromBranchId > 0 && (int) ($item->from_branch_id ?? 0) <= 0) {
            $item->from_branch_id = $fromBranchId;
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

    public function inboundMenuLabel(?User $hub = null): string
    {
        if (! $hub) {
            return __('message.from_yangon_to_mdy');
        }

        $isM2m = str_contains(strtolower((string) $hub->email), 'ygn2')
            || str_contains((string) $hub->name, 'M2M');

        return __('message.from_hub_to_mdy', [
            'hub' => $isM2m ? 'M2M' : $hub->name,
        ]);
    }

    public function hubAccountIds(): array
    {
        return $this->accounts()->pluck('id')->map(fn ($id) => (int) $id)->filter()->values()->all();
    }

    public function applyYangonInbound($query)
    {
        $hubIds = $this->hubAccountIds();
        if ($hubIds === []
            || ! Schema::hasColumn('dispatch_order_items', 'hub_user_id')
            || ! Schema::hasColumn('dispatch_order_items', 'mdy_inbox_at')
        ) {
            return $query->whereRaw('0 = 1');
        }

        return $query
            ->whereIn('hub_user_id', $hubIds)
            ->whereNotNull('mdy_inbox_at')
            ->whereNull('mdy_accepted_at');
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

    public function inboundCount(?int $hubId = null): int
    {
        if (! Schema::hasColumn('dispatch_order_items', 'mdy_inbox_at')) {
            return 0;
        }

        $query = \App\Models\DispatchOrderItem::query()
            ->where('status', 'assigned');
        if ($hubId && $hubId > 0) {
            $this->applyMdyInbound($query, $hubId);
        } else {
            $this->applyYangonInbound($query);
        }

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
