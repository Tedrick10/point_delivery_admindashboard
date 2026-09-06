<?php

namespace App\Services;

use App\Models\DispatchOrderItem;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DispatchOrderWorkflowService
{
    public function collectedItems(Order $order): Collection
    {
        return $order->dispatchItems()
            ->where('status', 'collected')
            ->get();
    }

    public function adminProgress(Order $order): array
    {
        $items = $this->collectedItems($order);
        $total = $items->count();
        $updated = $items->whereNotNull('admin_updated_at')->count();

        return [
            'total' => $total,
            'updated' => $updated,
            'is_complete' => $total > 0 && $updated === $total,
        ];
    }

    /**
     * Items past Pick Up / Assign 100 admin work — Admin Status should stay Completed.
     */
    public function advancedAdminItemStatuses(): array
    {
        return ['assigned', 'courier_assigned', 'courier_departed', 'pending', 'completed'];
    }

    public function hasAdvancedAdminItems(Order $order): bool
    {
        return $order->dispatchItems()
            ->whereIn('status', $this->advancedAdminItemStatuses())
            ->exists();
    }

    public function adminStatusLabel(Order $order): string
    {
        // Once any item reached Assign 100 / delivery stages, admin work for that
        // flow is done — keep Completed even if newer collected rows exist.
        if ($this->hasAdvancedAdminItems($order)) {
            return __('message.admin_status_completed');
        }

        $progress = $this->adminProgress($order);

        if ($progress['total'] === 0) {
            $expected = max(0, (int) ($order->total_parcel ?? 0));
            if ($expected > 0 && $this->isUserAppDispatchOrder($order)) {
                return __('message.admin_status_uncompleted', [
                    'updated' => 0,
                    'total' => $expected,
                ]);
            }

            return '-';
        }

        if ($progress['is_complete']) {
            return __('message.admin_status_completed');
        }

        return __('message.admin_status_uncompleted', [
            'updated' => $progress['updated'],
            'total' => $progress['total'],
        ]);
    }

    public function adminStatusClass(Order $order): string
    {
        if ($this->hasAdvancedAdminItems($order)) {
            return 'pds-dispatch-status-admin-completed';
        }

        $progress = $this->adminProgress($order);

        if ($progress['total'] === 0) {
            if ((int) ($order->total_parcel ?? 0) > 0 && $this->isUserAppDispatchOrder($order)) {
                return 'pds-dispatch-status-admin-uncompleted';
            }

            return 'pds-dispatch-status-neutral';
        }

        return $progress['is_complete']
            ? 'pds-dispatch-status-admin-completed'
            : 'pds-dispatch-status-admin-uncompleted';
    }

    public function riderStatusLabel(Order $order): string
    {
        $choiceLabel = pickupErrorChoiceLabel($order->pickup_error_choice ?? null);
        if ($choiceLabel && in_array((string) ($order->pickup_error_choice ?? ''), ['cancel', 'express'], true)) {
            return $choiceLabel;
        }

        if (($order->status ?? '') === 'pickup_error') {
            return __('message.rider_status_pick_up_error');
        }

        if (($order->status ?? '') === 'cancelled' && $this->isPickupErrorCancelled($order)) {
            return __('message.rider_status_pick_up_cancelled');
        }

        if (empty($order->delivery_man_id)) {
            return __('message.rider_status_pick_up_unassigned');
        }

        if ($this->hasPhysicalPickupCompleted($order)) {
            return __('message.rider_status_pick_up_done');
        }

        return __('message.rider_status_pick_up_assigned');
    }

    public function riderStatusClass(Order $order): string
    {
        $choice = (string) ($order->pickup_error_choice ?? '');
        if (in_array($choice, ['cancel', 'express'], true)) {
            return $choice === 'express'
                ? 'pds-dispatch-status-rider-cancelled-express'
                : 'pds-dispatch-status-rider-cancelled-user';
        }

        if (($order->status ?? '') === 'pickup_error') {
            return 'pds-dispatch-status-rider-error';
        }

        if (($order->status ?? '') === 'cancelled' && $this->isPickupErrorCancelled($order)) {
            return 'pds-dispatch-status-rider-cancelled';
        }

        if (empty($order->delivery_man_id)) {
            return 'pds-dispatch-status-rider-unassigned';
        }

        if ($this->hasPhysicalPickupCompleted($order)) {
            return 'pds-dispatch-status-rider-done';
        }

        return 'pds-dispatch-status-rider-assigned';
    }

    public function isPickupErrorCancelled(Order $order): bool
    {
        if (($order->status ?? '') !== 'cancelled') {
            return false;
        }

        if (! empty($order->pickup_error_at)) {
            return true;
        }

        if (in_array((string) ($order->pickup_error_choice ?? ''), ['cancel', 'express'], true)) {
            return true;
        }

        $reason = (string) ($order->reason ?? '');

        return str_contains(strtolower($reason), 'pickup error')
            || str_contains($reason, 'User cancelled after pickup error')
            || str_contains($reason, 'User chose express after pickup error');
    }

    /**
     * Restore a Pick Up Cancelled order.
     * Before 11:30 Yangon → Order List (today pickup_datetime).
     * After 11:30 Yangon → Pre Pick Up (next-day pickup_datetime).
     */
    public function restorePickupCancelledOrder(Order $order): Order
    {
        if (! $this->isPickupErrorCancelled($order)) {
            throw new \InvalidArgumentException(__('message.order_not_editable'));
        }

        $tz = 'Asia/Yangon';
        $pickupDatetime = isSameDayOrderCutoffPassed()
            ? nextDayOrderReceivedDatetime()
            : \Carbon\Carbon::now($tz)->format('Y-m-d H:i:s');

        $order->forceFill([
            'status' => 'create',
            'pickup_datetime' => $pickupDatetime,
            'delivery_man_id' => null,
            'assign_datetime' => null,
            'pickup_error_at' => null,
            'pickup_error_choice' => null,
            'pickup_error_choice_at' => null,
        ])->save();

        \App\Models\DispatchOrderItem::where('order_id', $order->id)
            ->whereIn('status', ['assigned', 'courier_assigned', 'courier_departed', 'pending'])
            ->update([
                'status' => 'collected',
                'delivery_man_id' => null,
                'assigned_at' => null,
            ]);

        return $order->fresh();
    }

    public function hasPhysicalPickupCompleted(Order $order): bool
    {
        return in_array($order->status, ['courier_picked_up', 'courier_departed', 'completed'], true);
    }

    public function isReadyForAssign100(Order $order): bool
    {
        if (empty($order->delivery_man_id)) {
            return false;
        }

        // Both Admin Done and Rider Pick Up Completed are required.
        if (!$this->hasPhysicalPickupCompleted($order)) {
            return false;
        }

        $progress = $this->adminProgress($order);

        if ($progress['is_complete']) {
            return true;
        }

        if ($progress['total'] === 0) {
            $targetCount = max(1, $order->dispatchItems()->where('status', 'collected')->count());
            if ($targetCount <= 1) {
                $targetCount = max(1, (int) $order->total_parcel);
            }
            $advancedCount = $order->dispatchItems()
                ->whereIn('status', ['assigned', 'courier_assigned', 'courier_departed', 'pending', 'completed'])
                ->count();

            return $advancedCount >= $targetCount;
        }

        return false;
    }

    /**
     * Admin may fill Item Count info only after a Pickup Rider is assigned.
     * Once an item is Delivered (completed), its info is locked.
     */
    public function canAdminEditDispatchItemInfo(Order $order, $item = null): bool
    {
        if (in_array($order->status ?? '', ['cancelled', 'completed', 'draft'], true)) {
            return false;
        }

        if (empty($order->delivery_man_id)) {
            return false;
        }

        if ($item !== null) {
            $itemStatus = is_object($item) ? (string) ($item->status ?? '') : '';
            if ($itemStatus === 'completed') {
                return false;
            }
        }

        return true;
    }

    /**
     * Admin Done tab: info complete, rider has not pressed Pick Up Completed yet.
     */
    public function isAdminDoneAwaitingRider(Order $order): bool
    {
        if (empty($order->delivery_man_id)) {
            return false;
        }

        if ($this->hasPhysicalPickupCompleted($order)) {
            return false;
        }

        return $this->isAdminDone($order);
    }

    /**
     * Rider Done tab: rider completed pick-up, admin info still incomplete.
     */
    public function isRiderDoneAwaitingAdmin(Order $order): bool
    {
        if (! $this->hasPhysicalPickupCompleted($order)) {
            return false;
        }

        return ! $this->isAdminDone($order);
    }

    public function shouldShowOnOrderList(Order $order): bool
    {
        if (!$order->dispatchItems()->exists()) {
            return false;
        }

        if ($this->isReadyForAssign100($order)) {
            return false;
        }

        $hasCollected = $this->collectedItems($order)->isNotEmpty();
        if (!$hasCollected) {
            $hasAdvancedItems = $order->dispatchItems()
                ->whereIn('status', ['assigned', 'courier_assigned'])
                ->exists();

            return !($hasAdvancedItems && !empty($order->delivery_man_id));
        }

        return true;
    }

    public function applyDispatchStatusFilter($query, ?string $status): void
    {
        if (!$status || $status === 'all') {
            return;
        }

        match ($status) {
            'admin_uncompleted' => $query->whereHas('dispatchItems', function ($item) {
                $item->where('status', 'collected')->whereNull('admin_updated_at');
            }),
            'admin_completed' => $query->whereNotNull('delivery_man_id')
                ->whereNotIn('status', ['courier_picked_up', 'courier_departed', 'completed', 'pickup_error', 'cancelled'])
                ->where(function ($q) {
                    $q->where(function ($sub) {
                        $sub->whereHas('dispatchItems', function ($item) {
                            $item->where('status', 'collected');
                        })->whereDoesntHave('dispatchItems', function ($item) {
                            $item->where('status', 'collected')->whereNull('admin_updated_at');
                        });
                    })->orWhere(function ($sub) {
                        $sub->whereDoesntHave('dispatchItems', function ($item) {
                            $item->where('status', 'collected');
                        })->whereHas('dispatchItems', function ($item) {
                            $item->whereIn('status', ['assigned', 'courier_assigned', 'courier_departed', 'pending', 'completed']);
                        });
                    });
                }),
            'rider_pick_up_unassigned' => $query->whereNull('delivery_man_id'),
            'rider_pick_up_assigned' => $query->whereNotNull('delivery_man_id')
                ->whereNotIn('status', ['courier_picked_up', 'courier_departed', 'completed', 'pickup_error', 'cancelled']),
            'rider_pick_up_error' => $query->where('status', 'pickup_error')
                ->where(function ($q) {
                    $q->whereNull('pickup_error_choice')
                        ->orWhereNotIn('pickup_error_choice', ['cancel', 'express']);
                }),
            'rider_pick_up_cancelled' => $query->where('status', 'cancelled')
                ->where(function ($q) {
                    $q->whereNotNull('pickup_error_at')
                        ->orWhereIn('pickup_error_choice', ['cancel', 'express'])
                        ->orWhere('reason', 'like', '%pickup error%')
                        ->orWhere('reason', 'like', '%User cancelled after pickup error%')
                        ->orWhere('reason', 'like', '%User chose express after pickup error%');
                }),
            'rider_pick_up_done' => $query->whereNotNull('delivery_man_id')
                ->where('status', 'courier_picked_up')
                ->where(function ($q) {
                    // Rider done first — Admin Item Info still incomplete.
                    $q->whereHas('dispatchItems', function ($item) {
                        $item->where('status', 'collected')->whereNull('admin_updated_at');
                    })->orWhereDoesntHave('dispatchItems', function ($item) {
                        $item->where('status', 'collected');
                    });
                }),
            default => null,
        };
    }

    public function applyOrderListQuery($query)
    {
        $query->where(function ($outer) {
            // Existing workflow: orders that already have dispatch items.
            $outer->where(function ($withItems) {
                $withItems->whereHas('dispatchItems')
                    ->where('status', '!=', 'pickup_error')
                    ->where('status', '!=', 'cancelled')
                    ->where(function ($q) {
                        $q->where(function ($pending) {
                            $pending->whereHas('dispatchItems', function ($item) {
                                $item->where('status', 'collected');
                            })->where(function ($adminOrRider) {
                                $adminOrRider->whereHas('dispatchItems', function ($item) {
                                    $item->where('status', 'collected')->whereNull('admin_updated_at');
                                })->orWhereNull('delivery_man_id');
                            });
                        })->orWhere(function ($advancedWithoutRider) {
                            $advancedWithoutRider->whereNull('delivery_man_id')
                                ->whereHas('dispatchItems', function ($item) {
                                    $item->whereIn('status', ['assigned', 'courier_assigned']);
                                });
                        })->orWhere(function ($pickUpAssigned) {
                            // Resolved pickup_error / normal pick-up assigned orders.
                            $pickUpAssigned->whereNotNull('delivery_man_id')
                                ->whereIn('status', ['courier_assigned', 'courier_arrived', 'active'])
                                ->whereHas('dispatchItems');
                        })->orWhere(function ($riderDone) {
                            // Rider completed pick-up; stay on Order List until Assign 100.
                            $riderDone->whereNotNull('delivery_man_id')
                                ->where('status', 'courier_picked_up')
                                ->whereHas('dispatchItems', function ($item) {
                                    $item->where('status', 'collected');
                                });
                        })->orWhere(function ($adminDone) {
                            // Admin filled all item info; may still wait for Assign 100 sync.
                            $adminDone->whereNotNull('delivery_man_id')
                                ->whereNotIn('status', ['pickup_error', 'cancelled', 'completed', 'draft'])
                                ->whereHas('dispatchItems', function ($item) {
                                    $item->where('status', 'collected');
                                })
                                ->whereDoesntHave('dispatchItems', function ($item) {
                                    $item->where('status', 'collected')->whereNull('admin_updated_at');
                                });
                        });
                    });
            })
            // Text/Photo/Shop/Gate orders from User App may have zero items until
            // the client adds Item Count rows — still show them in Pick Up list.
            ->orWhere(function ($awaitingItems) {
                $awaitingItems->where(function ($flags) {
                    $flags->where('is_text_order', 1)
                        ->orWhere('is_photo_order', 1)
                        ->orWhere('is_shop_order', 1)
                        ->orWhere('is_gate_order', 1);
                })
                    ->whereIn('status', [
                        'create',
                        'active',
                        'courier_assigned',
                        'courier_arrived',
                        'courier_picked_up',
                    ])
                    ->whereDoesntHave('dispatchItems');
            });
        });

        // After 11:30 creates stay in Pre Order until next Yangon day 00:00.
        $this->excludeFuturePreOrders($query);

        return $query;
    }

    /**
     * Day-by-day counts for Order List tabs (All / Pick Up / Pick Up Rider / Rider Done / Admin Done).
     *
     * Date bounds must match DispatchOrderDataTable: pickup_datetime as Yangon wall clock,
     * created_at fallback as UTC (orders with null pickup_datetime).
     *
     * @return array<string, int>
     */
    public function orderListTabCounts(?string $fromDateRaw = null, ?string $toDateRaw = null, ?string $searchTerm = null): array
    {
        $tabs = [
            'all',
            'rider_pick_up_unassigned',
            'rider_pick_up_assigned',
            'rider_pick_up_done',
            'admin_completed',
        ];

        // Keep counts in sync with the table (Admin Done may reclaim premature Assign 100 rows).
        $this->reclaimPrematureAssign100Items();
        $this->healUtcRolloverReceivedDates();

        $yangonToday = Carbon::now('Asia/Yangon');
        $fromRaw = $fromDateRaw ?: $yangonToday->format('d-m-Y');
        $toRaw = $toDateRaw ?: $yangonToday->format('d-m-Y');

        $dateBounds = null;
        try {
            $dateBounds = parseDispatchFilterDateBounds($fromRaw, $toRaw);
        } catch (\Throwable $e) {
            $dateBounds = null;
        }

        $counts = [];
        foreach ($tabs as $tab) {
            $query = Order::query()->whereNull('deleted_at');
            $this->applyOrderListQuery($query);
            if ($tab !== 'all') {
                $this->applyDispatchStatusFilter($query, $tab);
            }

            if ($dateBounds) {
                $query->where(function ($dateQuery) use ($dateBounds) {
                    $dateQuery->whereBetween('pickup_datetime', [
                        $dateBounds['pickup_from'],
                        $dateBounds['pickup_to'],
                    ])->orWhere(function ($fallback) use ($dateBounds) {
                        $fallback->whereNull('pickup_datetime')
                            ->whereBetween('created_at', [
                                $dateBounds['created_from'],
                                $dateBounds['created_to'],
                            ]);
                    });
                });
            }

            $term = trim((string) $searchTerm);
            if ($term !== '') {
                $like = '%' . $term . '%';
                $query->where(function ($q) use ($like) {
                    $q->where('id', 'like', $like)
                        ->orWhere('description', 'like', $like)
                        ->orWhere('milisecond', 'like', $like)
                        ->orWhereHas('client', function ($client) use ($like) {
                            $client->where('name', 'like', $like)
                                ->orWhere('contact_number', 'like', $like)
                                ->orWhere('email', 'like', $like);
                        })
                        ->orWhereHas('delivery_man', function ($rider) use ($like) {
                            $rider->where('name', 'like', $like)
                                ->orWhere('contact_number', 'like', $like);
                        });
                });
            }

            $counts[$tab] = (int) $query->count();
        }

        return $counts;
    }

    /**
     * Hide next-day pre-orders from the main Order List until their received day.
     */
    public function excludeFuturePreOrders($query): void
    {
        $visibleBefore = preOrderVisibleFromDatetime();
        $query->where(function ($q) use ($visibleBefore) {
            $q->whereNull('pickup_datetime')
                ->orWhere('pickup_datetime', '<', $visibleBefore);
        });
    }

    /**
     * Dedicated sidebar lists for pickup_error / pickup cancelled / pre_order.
     */
    public function applyDedicatedPickupListQuery($query, string $dispatchStatus): void
    {
        // Express / User Cancel must leave Pick Up Error → Pick Up Cancelled.
        $this->healPickupErrorChoicesToCancelled();

        if ($dispatchStatus === 'rider_pick_up_error') {
            $query->where('status', 'pickup_error')
                ->where(function ($q) {
                    $q->whereNull('pickup_error_choice')
                        ->orWhereNotIn('pickup_error_choice', ['cancel', 'express']);
                });

            return;
        }

        if ($dispatchStatus === 'rider_pick_up_cancelled') {
            $query->where('status', 'cancelled')
                ->where(function ($q) {
                    $q->whereNotNull('pickup_error_at')
                        ->orWhereIn('pickup_error_choice', ['cancel', 'express'])
                        ->orWhere('reason', 'like', '%pickup error%')
                        ->orWhere('reason', 'like', '%User cancelled after pickup error%')
                        ->orWhere('reason', 'like', '%User chose express after pickup error%');
                });

            return;
        }

        if ($dispatchStatus === 'pre_order') {
            $visibleFrom = preOrderVisibleFromDatetime();
            $query->whereNotNull('pickup_datetime')
                ->where('pickup_datetime', '>=', $visibleFrom)
                ->whereNotIn('status', ['cancelled', 'pickup_error', 'completed', 'draft']);
        }
    }

    /**
     * If client chose cancel/express but order is still not cancelled, move to cancelled.
     */
    public function healPickupErrorChoicesToCancelled(): int
    {
        return Order::query()
            ->whereIn('pickup_error_choice', ['cancel', 'express'])
            ->where('status', '!=', 'cancelled')
            ->update([
                'status' => 'cancelled',
                'delivery_man_id' => null,
                'assign_datetime' => null,
            ]);
    }

    public function isDedicatedPickupList(?string $dispatchStatus): bool
    {
        return in_array($dispatchStatus, ['rider_pick_up_error', 'rider_pick_up_cancelled', 'pre_order'], true);
    }

    public function preOrderCount(): int
    {
        $visibleFrom = preOrderVisibleFromDatetime();

        return Order::query()
            ->whereNull('deleted_at')
            ->whereNotNull('pickup_datetime')
            ->where('pickup_datetime', '>=', $visibleFrom)
            ->whereNotIn('status', ['cancelled', 'pickup_error', 'completed', 'draft'])
            ->count();
    }

    /**
     * Next-day Pre Pick Up orders (not yet visible on the main Order List).
     * Rider assignment is blocked until the received day arrives.
     */
    public function isPrePickUpOrder(Order $order): bool
    {
        if (empty($order->pickup_datetime)) {
            return false;
        }

        if (in_array($order->status ?? '', ['cancelled', 'pickup_error', 'completed', 'draft'], true)) {
            return false;
        }

        return Carbon::parse($order->pickup_datetime)
            ->gte(Carbon::parse(preOrderVisibleFromDatetime()));
    }

    /**
     * Move a Pre Pick Up order onto today's Order List (set received/pickup to now).
     */
    public function movePrePickUpToOrderList(Order $order): Order
    {
        if (! $this->isPrePickUpOrder($order)) {
            throw new \InvalidArgumentException(__('message.order_not_editable'));
        }

        $tz = 'Asia/Yangon';
        $now = Carbon::now($tz)->format('Y-m-d H:i:s');

        $order->forceFill([
            'pickup_datetime' => $now,
            'date' => $now,
            'delivery_datetime' => $order->delivery_datetime
                ? Carbon::parse($order->delivery_datetime)->timezone($tz)->format('Y-m-d H:i:s')
                : $now,
        ])->save();

        // Keep item received_date in sync with the new Order List day.
        \App\Models\DispatchOrderItem::where('order_id', $order->id)
            ->where('status', 'collected')
            ->update(['received_date' => Carbon::now($tz)->toDateString()]);

        return $order->fresh();
    }

    public function syncOrderWorkflow(Order $order): void
    {
        $order->loadMissing('dispatchItems');

        // Admin Done alone must not land in Assign 100 — reclaim if rider
        // has not pressed Pick Up Completed yet.
        $this->reclaimPrematureAssign100ItemsForOrder($order);

        if (!$this->isReadyForAssign100($order)) {
            return;
        }

        $movedQuery = DispatchOrderItem::query()
            ->where('order_id', $order->id)
            ->where('status', 'collected');

        $items = (clone $movedQuery)->get();
        $moved = 0;

        foreach ($items as $item) {
            $item->status = 'assigned';
            $item->assigned_at = now();
            $item->received_date = Carbon::now('Asia/Yangon')->toDateString();
            $item->save();
            $moved++;
        }

        // Admin Done + Rider Pick Up Done → notify client once when items enter Assign 100.
        if ($moved > 0) {
            try {
                app(AppPushService::class)->notifyClientPickupReady($order->fresh());
            } catch (\Throwable $e) {
                \Log::warning('push failed after pickup ready', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Assign 100 pool items use Yangon calendar day for received_date (day-by-day lists).
     */
    public function refreshAssign100PoolReceivedDates(): int
    {
        $today = Carbon::now('Asia/Yangon')->toDateString();

        return DispatchOrderItem::query()
            ->where('status', 'assigned')
            ->whereNull('delivery_man_id')
            ->where(function ($query) use ($today) {
                $query->whereNull('received_date')
                    ->orWhere('received_date', '<', $today);
            })
            ->update(['received_date' => $today]);
    }

    /**
     * Move Assign 100 "assigned" items back to collected when the rider
     * has not completed physical pick-up yet (Admin Done alone is not enough).
     */
    public function reclaimPrematureAssign100ItemsForOrder(Order $order): int
    {
        if ($this->hasPhysicalPickupCompleted($order)) {
            return 0;
        }

        return DispatchOrderItem::query()
            ->where('order_id', $order->id)
            ->where('status', 'assigned')
            ->update([
                'status' => 'collected',
                'assigned_at' => null,
            ]);
    }

    /**
     * Bulk reclaim for Assign 100 page / sidebar counts.
     */
    public function reclaimPrematureAssign100Items(): int
    {
        return DispatchOrderItem::query()
            ->where('status', 'assigned')
            ->whereHas('order', function ($q) {
                $q->whereNotIn('status', ['courier_picked_up', 'courier_departed', 'completed']);
            })
            ->update([
                'status' => 'collected',
                'assigned_at' => null,
            ]);
    }

    /**
     * New Order used UTC "today" before Yangon midnight+6:30, so early-morning
     * creates landed on yesterday. Move those same-day creates onto Yangon today.
     */
    public function healUtcRolloverReceivedDates(): int
    {
        $tz = 'Asia/Yangon';
        $today = Carbon::now($tz)->toDateString();
        $yesterday = Carbon::now($tz)->copy()->subDay()->toDateString();
        $todayStartUtc = Carbon::now($tz)->copy()->startOfDay()->utc()->format('Y-m-d H:i:s');

        $ids = Order::query()
            ->where('created_at', '>=', $todayStartUtc)
            ->whereDate('pickup_datetime', $yesterday)
            ->whereTime('pickup_datetime', '00:00:00')
            ->pluck('id');

        if ($ids->isEmpty()) {
            return 0;
        }

        Order::query()->whereIn('id', $ids)->update([
            'pickup_datetime' => $today.' 00:00:00',
            'date' => $today.' 00:00:00',
        ]);

        DispatchOrderItem::query()
            ->whereIn('order_id', $ids)
            ->whereDate('received_date', $yesterday)
            ->update(['received_date' => $today]);

        return $ids->count();
    }

    public function isAdminDone(Order $order): bool
    {
        $progress = $this->adminProgress($order);

        if ($progress['total'] === 0) {
            return $order->dispatchItems()
                ->whereIn('status', ['assigned', 'courier_assigned', 'courier_departed', 'pending', 'completed'])
                ->exists();
        }

        return $progress['is_complete'];
    }

    protected function isUserAppDispatchOrder(Order $order): bool
    {
        return (int) ($order->is_photo_order ?? 0) === 1
            || (int) ($order->is_text_order ?? 0) === 1
            || (int) ($order->is_shop_order ?? 0) === 1
            || (int) ($order->is_gate_order ?? 0) === 1;
    }

    public function isRiderDone(Order $order): bool
    {
        return !empty($order->delivery_man_id);
    }

    /**
     * Statuses the User App Order Detail List may show (excludes rider_added rows).
     * Includes Assign 100 "Unassigned" (status=assigned) and later delivery stages.
     */
    public function clientVisibleItemStatuses(): array
    {
        return ['collected', 'assigned', 'courier_assigned', 'courier_departed', 'pending', 'completed'];
    }

    /**
     * Item statuses visible in Rider Delivery after Assign 100.
     */
    public function deliveryItemStatuses(): array
    {
        return ['courier_assigned', 'courier_departed', 'pending', 'completed'];
    }

    /**
     * User App 「အပ်လိုက်သည့် ပါဆယ်များ」— includes Assign 100 pool + later stages.
     */
    public function clientDeliveryItemStatuses(): array
    {
        return ['assigned', 'courier_assigned', 'courier_departed', 'pending', 'completed'];
    }

    /**
     * Allowed next statuses for Rider Delivery text buttons.
     * Assigned → On Way; On Way → Delivered / Pending(+remark);
     * Pending → Delivered only; any path to Delivered is final (no further changes).
     */
    public function allowedDeliveryStatusActions(DispatchOrderItem $item): array
    {
        $status = (string) ($item->status ?? '');

        // Delivered is final. Stale delivery_locked on non-delivered items must not block.
        if ($status === 'completed') {
            return [];
        }

        return match ($status) {
            'courier_assigned' => ['courier_departed'],
            'courier_departed' => ['completed', 'pending'],
            'pending' => ['completed'],
            default => [],
        };
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function assertDeliveryStatusTransition(DispatchOrderItem $item, string $toStatus, ?string $remark = null): void
    {
        $toStatus = trim($toStatus);
        $allowed = $this->allowedDeliveryStatusActions($item);

        if (! in_array($toStatus, $allowed, true)) {
            throw new \InvalidArgumentException(__('message.delivery_item_status_not_allowed'));
        }

        if ($toStatus === 'pending') {
            $remark = trim((string) $remark);
            if ($remark === '') {
                throw new \InvalidArgumentException(__('message.delivery_item_pending_remark_required'));
            }
        }
    }

    /**
     * Client-facing status label for User App lists.
     * Assign 100 pool (status=assigned) and courier_assigned both show as Assigned.
     */
    public function clientItemStatusLabel(DispatchOrderItem $item): string
    {
        $status = (string) ($item->status ?? '');

        if ($status === 'completed') {
            if (! empty($item->admin_finished_at)) {
                return __('message.follow_up_status_finished');
            }
            if (! empty($item->admin_completed_at)) {
                return __('message.follow_up_status_completed');
            }

            return __('message.follow_up_status_delivered');
        }

        return match ($status) {
            'collected' => __('message.follow_up_status_pick_up'),
            'assigned', 'courier_assigned' => __('message.follow_up_status_assigned'),
            'courier_departed' => __('message.follow_up_status_on_way'),
            'pending' => __('message.follow_up_status_pending'),
            default => strtoupper(str_replace('_', ' ', (string) ($item->status ?: 'collected'))),
        };
    }

    /**
     * User App may edit/add/delete text-order items until Admin Status is
     * Completed and Rider Status is Pick Up Done (or the order is locked).
     */
    public function clientItemsEditable(Order $order): bool
    {
        if (in_array($order->status, ['completed', 'cancelled', 'courier_departed'], true)) {
            return false;
        }

        if ($this->isAdminDone($order) && $this->hasPhysicalPickupCompleted($order)) {
            return false;
        }

        return true;
    }

    public function followUpStatusKey(DispatchOrderItem $item): string
    {
        $order = $item->order;
        if (!$order) {
            return 'pick_up';
        }

        $choice = (string) ($order->pickup_error_choice ?? '');
        if (in_array($choice, ['cancel', 'express'], true)
            || ($order->status ?? '') === 'cancelled'
            || $this->isPickupErrorCancelled($order)) {
            return 'cancelled';
        }

        if ($item->status === 'completed') {
            if (! empty($item->admin_finished_at)) {
                return 'finished';
            }

            return ! empty($item->admin_completed_at) ? 'completed' : 'delivered';
        }

        if ($item->status === 'pending') {
            return 'pending';
        }

        if ($item->status === 'courier_departed') {
            return 'on_way';
        }

        if ($item->status === 'courier_assigned') {
            return 'assigned';
        }

        if ($item->status === 'assigned' && $this->isAdminDone($order) && $this->isRiderDone($order)) {
            return 'assigned_100';
        }

        if ($item->status === 'collected' && $this->isAdminDone($order) && $this->isRiderDone($order)) {
            return 'assigned_100';
        }

        if (!$this->isAdminDone($order) || !$this->isRiderDone($order)) {
            return 'pick_up';
        }

        if ($item->status === 'assigned') {
            return 'assigned_100';
        }

        return 'pick_up';
    }

    public function followUpItemStatusLabel(DispatchOrderItem $item): string
    {
        return match ($this->followUpStatusKey($item)) {
            'pick_up' => __('message.follow_up_status_pick_up'),
            'assigned' => __('message.follow_up_status_assigned'),
            'assigned_100' => __('message.follow_up_status_assigned_100'),
            'on_way' => __('message.follow_up_status_on_way'),
            'pending' => __('message.follow_up_status_pending'),
            'delivered' => __('message.follow_up_status_delivered'),
            'completed' => __('message.follow_up_status_completed'),
            'finished' => __('message.follow_up_status_finished'),
            'cancelled' => __('message.cancelled'),
            default => strtoupper((string) $item->status),
        };
    }

    public function followUpItemStatusClass(DispatchOrderItem $item): string
    {
        return match ($this->followUpStatusKey($item)) {
            'pick_up' => 'pds-dispatch-status-follow-pick-up',
            'assigned' => 'pds-dispatch-status-follow-assigned',
            'assigned_100' => 'pds-dispatch-status-follow-assigned-100',
            'on_way' => 'pds-dispatch-status-follow-on-way',
            'pending' => 'pds-dispatch-status-follow-pending',
            'delivered' => 'pds-dispatch-status-follow-delivered',
            'completed' => 'pds-dispatch-status-follow-completed',
            'finished' => 'pds-dispatch-status-follow-finished',
            'cancelled' => 'pds-dispatch-status-follow-cancelled',
            default => 'pds-dispatch-status-neutral',
        };
    }

    public function assign100ItemStatusLabel(DispatchOrderItem $item): string
    {
        return !empty($item->delivery_man_id)
            ? __('message.follow_up_status_assigned')
            : __('message.assign_100_status_unassigned');
    }

    public function assign100ItemStatusClass(DispatchOrderItem $item): string
    {
        return !empty($item->delivery_man_id)
            ? 'pds-dispatch-status-follow-assigned'
            : 'pds-dispatch-status-assign-100-unassigned';
    }

    public function syncItemStatusFromDeliveryApp(int $orderId, int $deliveryManId, string $orderStatus): void
    {
        if (!in_array($orderStatus, ['courier_departed', 'completed'], true)) {
            return;
        }

        $itemQuery = DispatchOrderItem::query()
            ->where('order_id', $orderId)
            ->where('delivery_man_id', $deliveryManId);

        if ($orderStatus === 'courier_departed') {
            $itemQuery->where('status', 'courier_assigned')
                ->update(['status' => 'courier_departed']);
            return;
        }

        $itemQuery->whereIn('status', ['courier_assigned', 'courier_departed'])
            ->update([
                'status' => 'completed',
                'delivered_at' => now(),
                'rider_remit_at' => null,
                'rider_remit_date' => resolveRiderRemitDate((int) $deliveryManId),
            ]);
    }

    public function markItemAdminUpdated(DispatchOrderItem $item): void
    {
        if ($item->admin_updated_at === null) {
            $item->forceFill(['admin_updated_at' => now()])->save();
        }
    }
}
