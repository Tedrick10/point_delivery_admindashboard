<?php

namespace App\Services;

use App\Models\DispatchOrderItem;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DispatchOrderAuditService
{
    public const TYPE_ORDER_CREATED = 'dispatch_audit_order_created';
    public const TYPE_PICKUP_RIDER_ASSIGNED = 'dispatch_audit_pickup_rider_assigned';
    public const TYPE_ADMIN_ITEM_INFO = 'dispatch_audit_admin_item_info';
    public const TYPE_RIDER_ITEM_INFO = 'dispatch_audit_rider_item_info';
    public const TYPE_PICKUP_COMPLETED = 'dispatch_audit_pickup_completed';
    public const TYPE_DELIVERY_RIDER_ASSIGNED = 'dispatch_audit_delivery_rider_assigned';
    public const TYPE_RESTORED = 'dispatch_audit_restored';
    public const TYPE_USER_CHOICE = 'dispatch_audit_user_choice';
    public const TYPE_ITEM_CREATED = 'dispatch_audit_item_created';
    public const TYPE_ITEM_DELETED = 'dispatch_audit_item_deleted';
    public const TYPE_OS_ITEM_INFO = 'dispatch_audit_os_item_info';
    public const TYPE_PHOTO_UPLOADED = 'dispatch_audit_photo_uploaded';
    public const TYPE_PRE_PICKUP_MOVED = 'dispatch_audit_pre_pickup_moved';
    public const TYPE_MOVED_TO_ASSIGN_100 = 'dispatch_audit_moved_to_assign_100';
    public const TYPE_DELIVERY_ITEM_STATUS = 'dispatch_audit_delivery_item_status';
    public const TYPE_DELI_AMOUNT = 'dispatch_audit_deli_amount';

    public function record(Order $order, string $type, string $message, array $data = []): OrderHistory
    {
        $actor = auth()->user();

        return OrderHistory::create([
            'order_id' => $order->id,
            'datetime' => now(),
            'history_type' => $type,
            'history_message' => $message,
            'history_data' => json_encode(array_merge([
                'actor_id' => $actor?->id,
                'actor_name' => $actor?->name,
                'actor_type' => $actor?->user_type,
            ], $data)),
        ]);
    }

    public function logOrderCreated(Order $order): void
    {
        $clientName = optional($order->client)->name ?: '-';
        $time = $this->formatTime($order->created_at ?? now());

        $this->record($order, self::TYPE_ORDER_CREATED, $this->t('dispatch_audit_order_created', [
            'client' => $clientName,
            'order' => $order->id,
            'time' => $time,
        ]), [
            'client_id' => $order->client_id,
            'client_name' => $clientName,
        ]);
    }

    public function logPickupRiderAssigned(Order $order, User $rider, ?User $admin = null): void
    {
        $admin = $admin ?: auth()->user();
        $adminName = $admin?->name ?: $this->t('dispatch_audit_account_admin');
        $time = $this->formatTime(now());

        $this->record($order, self::TYPE_PICKUP_RIDER_ASSIGNED, $this->t('dispatch_audit_pickup_rider_assigned', [
            'admin' => $adminName,
            'order' => $order->id,
            'rider' => $rider->name,
            'time' => $time,
        ]), [
            'admin_id' => $admin?->id,
            'admin_name' => $adminName,
            'rider_id' => $rider->id,
            'rider_name' => $rider->name,
        ]);
    }

    public function logAdminItemInfo(Order $order, DispatchOrderItem $item, ?User $admin = null, ?array $before = null): void
    {
        $admin = $admin ?: auth()->user();
        $adminName = $admin?->name ?: $this->t('dispatch_audit_account_admin');
        $itemLabel = $item->code ?: ('#' . $item->id);
        $after = $this->itemSnapshot($item);
        if (! $this->hasItemFieldChanges($before, $after, 'admin')) {
            return;
        }
        $actionLabel = $this->inferAdminActionLabel($before, $after);
        $details = $this->formatItemDetails($after, $before, $actionLabel);

        $this->record($order, self::TYPE_ADMIN_ITEM_INFO, $this->t('dispatch_audit_admin_item_info', [
            'admin' => $adminName,
            'order' => $order->id,
            'item' => $itemLabel,
        ]), [
            'admin_id' => $admin?->id,
            'admin_name' => $adminName,
            'item_id' => $item->id,
            'item_code' => $item->code,
            'details' => $details,
            'before' => $before,
            'after' => $after,
            'action' => $actionLabel,
            'actor_role' => 'admin',
        ]);

        $this->maybeLogDeliAmountChange($order, $item, $before, $after, $admin, 'admin');
    }

    public function logRiderItemInfo(
        Order $order,
        DispatchOrderItem $item,
        ?User $rider = null,
        ?array $before = null,
        ?string $action = null
    ): void {
        $rider = $rider ?: auth()->user();
        $riderName = $rider?->name
            ?: (optional($order->delivery_man)->name ?: $this->t('dispatch_audit_account_rider'));
        $itemLabel = $item->code ?: ('#' . $item->id);
        $after = $this->itemSnapshot($item);
        if (! $this->hasItemFieldChanges($before, $after, 'rider')) {
            return;
        }
        $actionLabel = $action ?: $this->inferRiderActionLabel($before, $after);
        $details = $this->formatItemDetails($after, $before, $actionLabel);

        $this->record($order, self::TYPE_RIDER_ITEM_INFO, $this->t('dispatch_audit_rider_item_info', [
            'rider' => $riderName,
            'order' => $order->id,
            'item' => $itemLabel,
        ]), [
            'rider_id' => $rider?->id,
            'rider_name' => $riderName,
            'item_id' => $item->id,
            'item_code' => $item->code,
            'details' => $details,
            'before' => $before,
            'after' => $after,
            'action' => $actionLabel,
            'actor_role' => 'rider',
        ]);

        $this->maybeLogDeliAmountChange($order, $item, $before, $after, $rider, 'rider');
    }

    public function logPickupCompleted(Order $order, ?User $rider = null): void
    {
        $rider = $rider ?: auth()->user() ?: $order->delivery_man;
        $riderName = $rider?->name ?: '-';
        $details = $this->formatPickupCompletedDetails($order);

        $this->record($order, self::TYPE_PICKUP_COMPLETED, $this->t('dispatch_audit_pickup_completed', [
            'rider' => $riderName,
            'order' => $order->id,
        ]), [
            'rider_id' => $rider?->id,
            'rider_name' => $riderName,
            'details' => $details,
            'action' => 'Pick Up Completed နှိပ်',
            'items' => $this->buildItemCards($order),
        ]);
    }

    public function logDeliveryRiderAssigned(Order $order, User $rider, int $itemCount = 1, ?User $admin = null): void
    {
        $admin = $admin ?: auth()->user();
        $adminName = $admin?->name ?: $this->t('dispatch_audit_account_admin');
        $time = $this->formatTime(now());

        $this->record($order, self::TYPE_DELIVERY_RIDER_ASSIGNED, $this->t('dispatch_audit_delivery_rider_assigned', [
            'admin' => $adminName,
            'order' => $order->id,
            'rider' => $rider->name,
            'count' => $itemCount,
            'time' => $time,
        ]), [
            'admin_id' => $admin?->id,
            'admin_name' => $adminName,
            'rider_id' => $rider->id,
            'rider_name' => $rider->name,
            'item_count' => $itemCount,
        ]);
    }

    public function logDeliveryItemStatus(
        Order $order,
        DispatchOrderItem $item,
        string $fromStatus,
        string $toStatus,
        ?User $rider = null,
        ?string $remark = null
    ): void {
        $rider = $rider ?: auth()->user();
        $riderName = $rider?->name ?: $this->t('dispatch_audit_account_rider');
        $fromLabel = $this->deliveryStatusLabel($fromStatus);
        $toLabel = $this->deliveryStatusLabel($toStatus);
        $itemCode = trim((string) ($item->code ?: $item->id));
        $remark = trim((string) ($remark ?? ''));

        $messageKey = match ($toStatus) {
            'courier_departed' => 'dispatch_audit_item_on_way',
            'completed' => 'dispatch_audit_item_delivered',
            'pending' => 'dispatch_audit_item_pending',
            default => 'dispatch_audit_item_status',
        };

        $this->record($order, self::TYPE_DELIVERY_ITEM_STATUS, $this->t($messageKey, [
            'rider' => $riderName,
            'order' => $order->id,
            'item' => $itemCode,
            'from' => $fromLabel,
            'to' => $toLabel,
        ]), [
            'rider_id' => $rider?->id,
            'rider_name' => $riderName,
            'item_id' => $item->id,
            'item_code' => $itemCode,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'from_label' => $fromLabel,
            'to_label' => $toLabel,
            'action' => $fromLabel.' → '.$toLabel,
            'remark' => $remark !== '' ? $remark : null,
            'reason' => $remark !== '' ? $remark : null,
            'photos' => $toStatus === 'pending' ? $this->pendingPhotosMeta($item) : [],
        ]);
    }

    /**
     * @return array<int, array{url:string,label:string}>
     */
    protected function pendingPhotosMeta(DispatchOrderItem $item): array
    {
        $photoId = (int) ($item->pending_photo_id ?? 0);
        if ($photoId <= 0) {
            return [];
        }

        $item->loadMissing('pendingPhotoMedia');
        $media = $item->pendingPhotoMedia;
        $url = $media ? (mediaPublicUrl($media) ?: mediaAbsoluteUrl($media)) : null;
        if (! $url) {
            return [];
        }

        return [[
            'url' => $url,
            'label' => 'Pending',
        ]];
    }

    /**
     * Fallback: resolve pending image from item when audit payload has no photos.
     *
     * @param  array<string, mixed>  $data
     * @return array<int, array{url:string,label:string}>
     */
    protected function pendingPhotosFromItemData(Order $order, array $data): array
    {
        $itemId = (int) ($data['item_id'] ?? 0);
        if ($itemId <= 0) {
            return [];
        }

        $order->loadMissing('dispatchItems.pendingPhotoMedia');
        $item = $order->dispatchItems->firstWhere('id', $itemId);
        if (! $item) {
            $item = DispatchOrderItem::query()->with('pendingPhotoMedia')->find($itemId);
        }
        if (! $item) {
            return [];
        }

        return $this->pendingPhotosMeta($item);
    }

    protected function deliveryStatusLabel(string $status): string
    {
        return match ($status) {
            'courier_assigned' => $this->t('follow_up_status_assigned'),
            'courier_departed' => $this->t('follow_up_status_on_way'),
            'pending' => $this->t('follow_up_status_pending'),
            'completed' => $this->t('follow_up_status_delivered'),
            default => strtoupper(str_replace('_', ' ', $status)),
        };
    }

    public function logRestored(Order $order, ?User $actor = null): void
    {
        $actor = $actor ?: auth()->user();
        $time = $this->formatTime(now());
        $isAdmin = $actor && in_array($actor->user_type, ['admin', 'demo_admin'], true);

        if ($isAdmin) {
            $adminName = $actor->name ?: $this->t('dispatch_audit_account_admin');
            $message = $this->t('dispatch_audit_restored_admin', [
                'admin' => $adminName,
                'order' => $order->id,
                'time' => $time,
            ]);
            $extra = [
                'restored_by' => 'admin',
                'admin_id' => $actor->id,
                'admin_name' => $adminName,
            ];
        } else {
            $clientName = $actor?->name
                ?: (optional($order->client)->name ?: $this->t('dispatch_audit_account_os'));
            $message = $this->t('dispatch_audit_restored_os', [
                'client' => $clientName,
                'order' => $order->id,
                'time' => $time,
            ]);
            $extra = [
                'restored_by' => 'os',
                'client_id' => $actor?->id ?: $order->client_id,
                'client_name' => $clientName,
            ];
        }

        $this->record($order, self::TYPE_RESTORED, $message, $extra);
    }

    public function logUserChoice(Order $order, string $choice, ?User $client = null): void
    {
        $client = $client ?: auth()->user();
        $clientName = $client?->name
            ?: (optional($order->client)->name ?: $this->t('dispatch_audit_account_os'));
        $choiceLabel = pickupErrorChoiceLabel($choice) ?: $choice;
        $reason = trim((string) ($order->reason ?? ''));

        $this->record($order, self::TYPE_USER_CHOICE, $this->t('dispatch_audit_user_choice', [
            'client' => $clientName,
            'order' => $order->id,
            'choice' => $choiceLabel,
        ]), [
            'client_id' => $client?->id ?: $order->client_id,
            'client_name' => $clientName,
            'choice' => $choice,
            'action' => $choiceLabel,
            'reason' => $reason !== '' ? $reason : null,
        ]);
    }

    public function logItemCreated(Order $order, DispatchOrderItem $item, string $actorRole = 'admin'): void
    {
        $actor = auth()->user();
        $itemLabel = $item->code ?: ('#'.$item->id);
        $after = $this->itemSnapshot($item);
        $actorName = $actor?->name ?: '-';

        [$type, $message, $extra] = match ($actorRole) {
            'rider' => [
                self::TYPE_ITEM_CREATED,
                $this->t('dispatch_audit_rider_item_created', [
                    'rider' => $actorName ?: $this->t('dispatch_audit_account_rider'),
                    'order' => $order->id,
                    'item' => $itemLabel,
                ]),
                [
                    'rider_id' => $actor?->id,
                    'rider_name' => $actorName,
                    'action' => 'ထပ်တိုး Item ထည့်',
                ],
            ],
            'client', 'os' => [
                self::TYPE_ITEM_CREATED,
                $this->t('dispatch_audit_os_item_created', [
                    'client' => $actorName ?: $this->t('dispatch_audit_account_os'),
                    'order' => $order->id,
                    'item' => $itemLabel,
                ]),
                [
                    'client_id' => $actor?->id,
                    'client_name' => $actorName,
                    'action' => 'Item ထည့်',
                ],
            ],
            default => [
                self::TYPE_ITEM_CREATED,
                $this->t('dispatch_audit_admin_item_created', [
                    'admin' => $actorName ?: $this->t('dispatch_audit_account_admin'),
                    'order' => $order->id,
                    'item' => $itemLabel,
                ]),
                [
                    'admin_id' => $actor?->id,
                    'admin_name' => $actorName,
                    'action' => 'Item ထည့်',
                ],
            ],
        };

        $this->record($order, $type, $message, array_merge($extra, [
            'item_id' => $item->id,
            'item_code' => $item->code,
            'after' => $after,
            'actor_role' => $actorRole,
        ]));
    }

    public function logItemDeleted(Order $order, array $itemMeta, string $actorRole = 'admin'): void
    {
        $actor = auth()->user();
        $actorName = $actor?->name ?: '-';
        $itemLabel = $itemMeta['code'] ?? ('#'.($itemMeta['id'] ?? '-'));

        $message = match ($actorRole) {
            'client', 'os' => $this->t('dispatch_audit_os_item_deleted', [
                'client' => $actorName ?: $this->t('dispatch_audit_account_os'),
                'order' => $order->id,
                'item' => $itemLabel,
            ]),
            default => $this->t('dispatch_audit_admin_item_deleted', [
                'admin' => $actorName ?: $this->t('dispatch_audit_account_admin'),
                'order' => $order->id,
                'item' => $itemLabel,
            ]),
        };

        $this->record($order, self::TYPE_ITEM_DELETED, $message, [
            'item_id' => $itemMeta['id'] ?? null,
            'item_code' => $itemMeta['code'] ?? null,
            'before' => $itemMeta['snapshot'] ?? null,
            'actor_role' => $actorRole,
            'admin_name' => $actorRole === 'admin' ? $actorName : null,
            'client_name' => in_array($actorRole, ['client', 'os'], true) ? $actorName : null,
            'action' => 'Item ဖျက်',
        ]);
    }

    public function logOsItemInfo(Order $order, DispatchOrderItem $item, ?User $client = null, ?array $before = null): void
    {
        $client = $client ?: auth()->user();
        $clientName = $client?->name
            ?: (optional($order->client)->name ?: $this->t('dispatch_audit_account_os'));
        $itemLabel = $item->code ?: ('#'.$item->id);
        $after = $this->itemSnapshot($item);
        if (! $this->hasItemFieldChanges($before, $after, 'os')) {
            return;
        }
        $actionLabel = $this->inferOsActionLabel($before, $after);
        $details = $this->formatItemDetails($after, $before, $actionLabel);

        $this->record($order, self::TYPE_OS_ITEM_INFO, $this->t('dispatch_audit_os_item_info', [
            'client' => $clientName,
            'order' => $order->id,
            'item' => $itemLabel,
        ]), [
            'client_id' => $client?->id ?: $order->client_id,
            'client_name' => $clientName,
            'item_id' => $item->id,
            'item_code' => $item->code,
            'details' => $details,
            'before' => $before,
            'after' => $after,
            'action' => $actionLabel,
            'actor_role' => 'os',
        ]);

        $this->maybeLogDeliAmountChange($order, $item, $before, $after, $client, 'os');
    }

    /**
     * Dedicated DeliAmount audit entry (also shown in Order List DeliAmount Audit Log).
     */
    public function maybeLogDeliAmountChange(
        Order $order,
        DispatchOrderItem $item,
        ?array $before,
        ?array $after,
        ?User $actor = null,
        string $actorRole = 'admin'
    ): void {
        if (! is_array($before) || ! is_array($after)) {
            return;
        }

        $from = round((float) ($before['deli_amount'] ?? 0), 2);
        $to = round((float) ($after['deli_amount'] ?? 0), 2);
        if (abs($from - $to) < 0.001) {
            return;
        }

        $actor = $actor ?: auth()->user();
        $actorName = $actor?->name ?: match ($actorRole) {
            'rider' => $this->t('dispatch_audit_account_rider'),
            'os', 'client' => $this->t('dispatch_audit_account_os'),
            default => $this->t('dispatch_audit_account_admin'),
        };
        $itemLabel = $item->code ?: ('#'.$item->id);
        $fromLabel = $this->formatMoney($from);
        $toLabel = $this->formatMoney($to);
        $sizeWeight = (int) ($after['weight'] ?? $item->weight ?? 0);
        $sizeLabel = $this->friendlySizeLabel($sizeWeight);
        $actionParts = [
            __('message.order').' #'.$order->id,
            $this->t('dispatch_audit_field_item').' — '.$itemLabel,
        ];
        if ($sizeLabel !== '—') {
            $actionParts[] = $this->t('dispatch_audit_field_size').' — '.$sizeLabel;
        }
        $actionParts[] = $this->t('dispatch_audit_field_deli_amount').' — '.$fromLabel.' → '.$toLabel;

        $this->record($order, self::TYPE_DELI_AMOUNT, $this->t('dispatch_audit_deli_amount_changed', [
            'actor' => $actorName,
            'order' => $order->id,
            'item' => $itemLabel,
            'from' => $fromLabel,
            'to' => $toLabel,
        ]), [
            'actor_role' => $actorRole,
            'admin_id' => $actorRole === 'admin' ? $actor?->id : null,
            'admin_name' => $actorRole === 'admin' ? $actorName : null,
            'rider_id' => $actorRole === 'rider' ? $actor?->id : null,
            'rider_name' => $actorRole === 'rider' ? $actorName : null,
            'client_id' => in_array($actorRole, ['os', 'client'], true) ? ($actor?->id ?: $order->client_id) : null,
            'client_name' => in_array($actorRole, ['os', 'client'], true) ? $actorName : null,
            'item_id' => $item->id,
            'item_code' => $item->code,
            'field' => 'deli_amount',
            'old_value' => $from,
            'new_value' => $to,
            'from' => $fromLabel,
            'to' => $toLabel,
            'weight' => $sizeWeight,
            'size' => $sizeLabel !== '—' ? $sizeLabel : null,
            'action' => implode(' · ', $actionParts),
            'action_parts' => $actionParts,
            'before' => [
                'deli_amount' => $from,
                'weight' => (int) ($before['weight'] ?? 0),
            ],
            'after' => [
                'deli_amount' => $to,
                'weight' => $sizeWeight,
            ],
        ]);
    }

    /**
     * DeliAmount-only timeline for one order (dedicated Audit Log modal).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function deliAmountTimelineForOrder(Order $order): Collection
    {
        $order->loadMissing(['orderHistoryasc', 'client', 'delivery_man', 'dispatchItems']);

        $entries = collect();

        foreach ($order->orderHistoryasc as $history) {
            $type = (string) ($history->history_type ?? '');
            $data = is_array($history->history_data) ? $history->history_data : [];
            $at = $history->datetime ?? $history->created_at;

            if ($type === self::TYPE_DELI_AMOUNT) {
                $presented = $this->presentHistory($history, $order);
                if ($presented) {
                    $entries->push($presented);
                }
                continue;
            }

            // Legacy: item-info rows that changed DeliAmount before dedicated type existed.
            if (! in_array($type, [
                self::TYPE_ADMIN_ITEM_INFO,
                self::TYPE_RIDER_ITEM_INFO,
                self::TYPE_OS_ITEM_INFO,
            ], true)) {
                continue;
            }

            $legacy = $this->legacyDeliAmountEntry($order, $data, $at);
            if ($legacy) {
                $entries->push($legacy);
            }
        }

        return $entries
            ->sortBy('sort_at')
            ->values()
            ->map(function (array $e) {
                unset($e['sort_at']);

                return $e;
            });
    }

    /**
     * DeliAmount audit rows for Order List date range (Yangon calendar days).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function deliAmountTimelineForRange(string $fromDay, string $toDay): Collection
    {
        $tz = 'Asia/Yangon';
        $fromDay = Carbon::parse($fromDay, $tz)->toDateString();
        $toDay = Carbon::parse($toDay, $tz)->toDateString();
        if ($toDay < $fromDay) {
            $toDay = $fromDay;
        }

        $histories = OrderHistory::query()
            ->with(['order:id'])
            ->whereIn('history_type', [
                self::TYPE_DELI_AMOUNT,
                self::TYPE_ADMIN_ITEM_INFO,
                self::TYPE_RIDER_ITEM_INFO,
                self::TYPE_OS_ITEM_INFO,
            ])
            ->where(function ($q) use ($fromDay, $toDay) {
                $q->where(function ($d) use ($fromDay, $toDay) {
                    $d->whereNotNull('datetime')
                        ->whereDate('datetime', '>=', $fromDay)
                        ->whereDate('datetime', '<=', $toDay);
                })->orWhere(function ($c) use ($fromDay, $toDay) {
                    $c->whereNull('datetime')
                        ->whereDate('created_at', '>=', $fromDay)
                        ->whereDate('created_at', '<=', $toDay);
                });
            })
            ->orderByDesc('datetime')
            ->orderByDesc('id')
            ->limit(500)
            ->get();

        $entries = collect();
        foreach ($histories as $history) {
            $order = $history->order;
            if (! $order) {
                $order = Order::query()->find((int) $history->order_id);
            }
            if (! $order) {
                continue;
            }

            $type = (string) ($history->history_type ?? '');
            $data = is_array($history->history_data) ? $history->history_data : [];
            $at = $history->datetime ?? $history->created_at;

            if ($type === self::TYPE_DELI_AMOUNT) {
                $presented = $this->presentHistory($history, $order);
                if ($presented) {
                    $presented['order_id'] = (int) $order->id;
                    $presented['sort_at'] = Carbon::parse($at)->timestamp;
                    $entries->push($presented);
                }
                continue;
            }

            $legacy = $this->legacyDeliAmountEntry($order, $data, $at);
            if ($legacy) {
                $entries->push($legacy);
            }
        }

        return $entries
            ->sortByDesc('sort_at')
            ->values()
            ->map(function (array $e) {
                unset($e['sort_at']);

                return $e;
            });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    protected function legacyDeliAmountEntry(Order $order, array $data, $at): ?array
    {
        $before = is_array($data['before'] ?? null) ? $data['before'] : null;
        $after = is_array($data['after'] ?? null) ? $data['after'] : null;
        if (! is_array($before) || ! is_array($after)) {
            return null;
        }
        $from = round((float) ($before['deli_amount'] ?? 0), 2);
        $to = round((float) ($after['deli_amount'] ?? 0), 2);
        if (abs($from - $to) < 0.001) {
            return null;
        }

        $fromLabel = $this->formatMoney($from);
        $toLabel = $this->formatMoney($to);
        $itemCode = (string) ($data['item_code'] ?? ('#'.($data['item_id'] ?? '-')));
        $actorName = $this->resolveName(
            $data['admin_name'] ?? null,
            $data['rider_name'] ?? null,
            $data['client_name'] ?? null,
            $data['actor_name'] ?? null,
            '-'
        );
        $sizeWeight = (int) ($after['weight'] ?? $data['weight'] ?? 0);
        if ($sizeWeight <= 0) {
            $itemId = (int) ($data['item_id'] ?? 0);
            if ($itemId > 0) {
                $order->loadMissing('dispatchItems');
                $liveItem = $order->dispatchItems->firstWhere('id', $itemId);
                $sizeWeight = (int) ($liveItem->weight ?? 0);
            }
        }
        $sizeLabel = $this->friendlySizeLabel($sizeWeight);
        $msg = $this->t('dispatch_audit_deli_amount_changed', [
            'actor' => $actorName,
            'order' => $order->id,
            'item' => $itemCode,
            'from' => $fromLabel,
            'to' => $toLabel,
        ]);

        $actionParts = [
            __('message.order').' #'.$order->id,
            $this->t('dispatch_audit_field_item').' — '.$itemCode,
        ];
        if ($sizeLabel !== '—') {
            $actionParts[] = $this->t('dispatch_audit_field_size').' — '.$sizeLabel;
        }
        $actionParts[] = $this->t('dispatch_audit_field_deli_amount').' — '.$fromLabel.' → '.$toLabel;

        return [
            'type' => self::TYPE_DELI_AMOUNT,
            'title' => $this->t('dispatch_audit_title_deli_amount'),
            'message' => $msg,
            'summary' => $msg,
            'time' => $this->formatTime($at),
            'icon' => 'fa-solid fa-coins',
            'tone' => 'edit',
            'order_id' => (int) $order->id,
            'action' => implode(' · ', $actionParts),
            'action_parts' => $actionParts,
            'reason' => null,
            'changes' => [],
            'items' => [],
            'photos' => [],
            'sort_at' => Carbon::parse($at)->timestamp,
        ];
    }

    public function logPhotoUploaded(Order $order, DispatchOrderItem $item, ?User $rider = null): void
    {
        $rider = $rider ?: auth()->user();
        $riderName = $rider?->name
            ?: (optional($order->delivery_man)->name ?: $this->t('dispatch_audit_account_rider'));
        $itemLabel = $item->code ?: ('#'.$item->id);
        $photo = $this->resolvePhotoMeta($item);

        $this->record($order, self::TYPE_PHOTO_UPLOADED, $this->t('dispatch_audit_photo_uploaded', [
            'rider' => $riderName,
            'order' => $order->id,
            'item' => $itemLabel,
        ]), [
            'rider_id' => $rider?->id,
            'rider_name' => $riderName,
            'item_id' => $item->id,
            'item_code' => $item->code,
            'action' => 'Parcel Photo တင်',
            'photo_id' => $photo['photo_id'] ?? null,
            'photo_url' => $photo['photo_url'] ?? null,
            'photos' => $photo['photo_url'] ? [[
                'url' => $photo['photo_url'],
                'label' => $itemLabel,
            ]] : [],
            'after' => $this->itemSnapshot($item),
            'actor_role' => 'rider',
        ]);
    }

    public function logPrePickupMoved(Order $order, ?User $admin = null): void
    {
        $admin = $admin ?: auth()->user();
        $adminName = $admin?->name ?: $this->t('dispatch_audit_account_admin');

        $this->record($order, self::TYPE_PRE_PICKUP_MOVED, $this->t('dispatch_audit_pre_pickup_moved', [
            'admin' => $adminName,
            'order' => $order->id,
        ]), [
            'admin_id' => $admin?->id,
            'admin_name' => $adminName,
            'action' => 'Pre Pick Up → Order List',
        ]);
    }

    public function logMovedToAssign100(Order $order, int $itemCount = 1, ?User $admin = null): void
    {
        $admin = $admin ?: auth()->user();
        $adminName = $admin?->name ?: $this->t('dispatch_audit_account_admin');

        $this->record($order, self::TYPE_MOVED_TO_ASSIGN_100, $this->t('dispatch_audit_moved_to_assign_100', [
            'admin' => $adminName,
            'order' => $order->id,
            'count' => $itemCount,
        ]), [
            'admin_id' => $admin?->id,
            'admin_name' => $adminName,
            'item_count' => $itemCount,
            'action' => 'Assign 100 သို့ ရွှေ့',
        ]);
    }

    /**
     * @return Collection<int, array{type:string,title:string,message:string,time:string,icon:string}>
     */
    public function timelineForOrder(Order $order): Collection
    {
        $order->loadMissing(['orderHistoryasc', 'client', 'delivery_man', 'dispatchItems']);

        $entries = collect();

        foreach ($order->orderHistoryasc as $history) {
            $presented = $this->presentHistory($history, $order);
            if ($presented) {
                $entries->push($presented);
            }
        }

        if ($entries->where('type', self::TYPE_ORDER_CREATED)->isEmpty()
            && $entries->where('type', 'create')->isEmpty()
            && $order->created_at) {
            $clientName = optional($order->client)->name ?: '-';
            $entries->push([
                'type' => self::TYPE_ORDER_CREATED,
                'title' => $this->t('dispatch_audit_title_order_created'),
                'message' => $this->t('dispatch_audit_order_created', [
                    'client' => $clientName,
                    'order' => $order->id,
                ]),
                'summary' => $this->t('dispatch_audit_order_created', [
                    'client' => $clientName,
                    'order' => $order->id,
                ]),
                'time' => $this->formatTime($order->created_at),
                'icon' => 'fa-solid fa-file-pen',
                'tone' => 'info',
                'action' => null,
                'reason' => null,
                'changes' => [],
                'items' => [],
                'sort_at' => Carbon::parse($order->created_at)->timestamp,
            ]);
        }

        if ($entries->whereIn('type', [self::TYPE_PICKUP_RIDER_ASSIGNED, 'courier_assigned'])->isEmpty()
            && ! empty($order->delivery_man_id)
            && $order->assign_datetime) {
            $msg = $this->t('dispatch_audit_pickup_rider_assigned', [
                'admin' => $this->t('dispatch_audit_account_admin'),
                'order' => $order->id,
                'rider' => optional($order->delivery_man)->name ?: '-',
            ]);
            $entries->push([
                'type' => self::TYPE_PICKUP_RIDER_ASSIGNED,
                'title' => $this->t('dispatch_audit_title_pickup_rider_assigned'),
                'message' => $msg,
                'summary' => $msg,
                'time' => $this->formatTime($order->assign_datetime),
                'icon' => 'fa-solid fa-user-check',
                'tone' => 'assign',
                'action' => null,
                'reason' => null,
                'changes' => [],
                'items' => [],
                'sort_at' => Carbon::parse($order->assign_datetime)->timestamp,
            ]);
        }

        // Do not invent Admin Item Info rows from admin_updated_at alone.
        // Only real before→after updates should appear.

        // Missing Parcel Photo rows (older pickups before photo audit existed).
        $order->loadMissing('dispatchItems.photoMedia');
        foreach ($order->dispatchItems as $item) {
            if ((int) ($item->photo_id ?? 0) <= 0) {
                continue;
            }
            $code = (string) ($item->code ?: ('#'.$item->id));
            $already = $entries->contains(function (array $e) use ($code, $item) {
                if (($e['type'] ?? '') !== self::TYPE_PHOTO_UPLOADED) {
                    return false;
                }
                $hay = (string) ($e['message'] ?? '').(string) ($e['summary'] ?? '').(string) ($e['action'] ?? '');

                return str_contains($hay, $code) || str_contains($hay, (string) $item->id);
            });
            if ($already) {
                continue;
            }
            $meta = $this->resolvePhotoMeta($item);
            $riderName = optional($order->delivery_man)->name ?: $this->t('dispatch_audit_account_rider');
            $photoAt = optional($item->photoMedia)->created_at
                ?? $item->updated_at
                ?? $order->updated_at
                ?? now();
            $msg = $this->t('dispatch_audit_photo_uploaded', [
                'rider' => $riderName,
                'order' => $order->id,
                'item' => $code,
            ]);
            $entries->push([
                'type' => self::TYPE_PHOTO_UPLOADED,
                'title' => $this->t('dispatch_audit_title_photo_uploaded'),
                'message' => $msg,
                'summary' => $msg,
                'time' => $this->formatTime($photoAt),
                'icon' => 'fa-solid fa-camera',
                'tone' => 'success',
                'action' => $this->t('dispatch_audit_field_item').' — '.$code,
                'action_parts' => [$this->t('dispatch_audit_field_item').' — '.$code],
                'reason' => null,
                'changes' => [],
                'items' => [],
                'photos' => ! empty($meta['photo_url']) ? [[
                    'url' => $meta['photo_url'],
                    'label' => $code,
                ]] : [],
                'sort_at' => Carbon::parse($photoAt)->timestamp,
            ]);
        }

        $choice = (string) ($order->pickup_error_choice ?? '');
        $choiceLabel = pickupErrorChoiceLabel($choice);
        if ($choiceLabel && in_array($choice, ['cancel', 'express'], true)) {
            $hasChoiceEntry = $entries->contains(function ($e) use ($choiceLabel) {
                $type = (string) ($e['type'] ?? '');
                if ($type === self::TYPE_USER_CHOICE) {
                    return true;
                }

                return str_contains((string) ($e['message'] ?? ''), $choiceLabel)
                    || str_contains((string) ($e['summary'] ?? ''), $choiceLabel);
            });
            if (! $hasChoiceEntry) {
                $choiceAt = $order->pickup_error_choice_at ?? $order->updated_at ?? now();
                $msg = $this->t('dispatch_audit_cancelled_choice', [
                    'client' => optional($order->client)->name ?: '-',
                    'order' => $order->id,
                    'choice' => $choiceLabel,
                ]);
                $reason = trim((string) ($order->reason ?? ''));
                $entries->push([
                    'type' => 'cancelled',
                    'title' => $this->t('dispatch_audit_title_cancelled'),
                    'message' => $msg,
                    'summary' => $msg,
                    'time' => $this->formatTime($choiceAt),
                    'icon' => 'fa-solid fa-ban',
                    'tone' => 'danger',
                    'action' => $choiceLabel,
                    'reason' => $reason !== '' ? $reason : null,
                    'changes' => [],
                    'items' => [],
                    'sort_at' => Carbon::parse($choiceAt)->timestamp,
                ]);
            }
        }

        $hasPickupAssignAudit = $entries->contains(
            fn ($e) => ($e['type'] ?? '') === self::TYPE_PICKUP_RIDER_ASSIGNED
        );
        $hasOrderCreatedAudit = $entries->contains(
            fn ($e) => ($e['type'] ?? '') === self::TYPE_ORDER_CREATED
        );
        $hasPickupDoneAudit = $entries->contains(
            fn ($e) => ($e['type'] ?? '') === self::TYPE_PICKUP_COMPLETED
        );

        return $entries
            ->reject(function (array $e) use ($hasPickupAssignAudit, $hasOrderCreatedAudit, $hasPickupDoneAudit) {
                $type = $e['type'] ?? '';
                if ($hasPickupAssignAudit && $type === 'courier_assigned') {
                    return true;
                }
                if ($hasOrderCreatedAudit && in_array($type, ['create', 'draft'], true)) {
                    return true;
                }
                if ($hasPickupDoneAudit && $type === 'courier_picked_up') {
                    return true;
                }

                return false;
            })
            ->sortBy('sort_at')
            ->values()
            ->reduce(function (Collection $carry, array $e) {
                $type = $e['type'] ?? '';
                // Keep only the first order-created style entry (avoid restore duplicates).
                if (in_array($type, [self::TYPE_ORDER_CREATED, 'create', 'draft'], true)) {
                    $already = $carry->contains(function (array $existing) {
                        return in_array($existing['type'] ?? '', [
                            self::TYPE_ORDER_CREATED,
                            'create',
                            'draft',
                        ], true);
                    });
                    if ($already) {
                        return $carry;
                    }
                }
                $carry->push($e);

                return $carry;
            }, collect())
            ->values()
            ->map(function (array $e) {
                unset($e['sort_at']);
                if (! isset($e['tone'])) {
                    $e['tone'] = $this->toneFor((string) ($e['type'] ?? ''));
                }
                $e['summary'] = $e['summary'] ?? $e['message'] ?? '';
                $e['action'] = $e['action'] ?? null;
                $e['reason'] = $e['reason'] ?? null;
                $e['items'] = $e['items'] ?? [];
                $e['photos'] = $e['photos'] ?? [];

                // Migrate any leftover "changes" rows into action pills, then drop changes.
                $actionParts = $e['action_parts'] ?? [];
                if ($actionParts === [] && ! empty($e['changes']) && is_array($e['changes'])) {
                    foreach ($e['changes'] as $row) {
                        if (! empty($row['value'])) {
                            $actionParts[] = ($row['label'] ?? 'Info').': '.$row['value'];
                        } elseif (isset($row['from'], $row['to'])) {
                            $actionParts[] = ($row['label'] ?? 'Info').': '.$row['from'].' → '.$row['to'];
                        }
                    }
                }
                if ($actionParts === [] && ! empty($e['action'])) {
                    $actionParts = preg_split('/\s*[·+]\s*/u', (string) $e['action'], -1, PREG_SPLIT_NO_EMPTY) ?: [(string) $e['action']];
                }
                $e['action_parts'] = $actionParts;
                if ($actionParts !== [] && empty($e['action'])) {
                    $e['action'] = implode(' · ', $actionParts);
                }
                $e['changes'] = [];

                return $e;
            });
    }

    protected function presentHistory(OrderHistory $history, Order $order): ?array
    {
        $type = (string) ($history->history_type ?? '');
        if ($type === '') {
            return null;
        }

        if (in_array($type, ['bid_placed', 'reject_bid', 'payment_status_message'], true)) {
            return null;
        }

        $at = $history->datetime ?? $history->created_at;
        $time = $this->formatTime($at);
        $data = is_array($history->history_data) ? $history->history_data : [];

        // Admin / Rider / OS item "updates": only show when something actually changed.
        if (in_array($type, [
            self::TYPE_ADMIN_ITEM_INFO,
            self::TYPE_RIDER_ITEM_INFO,
            self::TYPE_OS_ITEM_INFO,
        ], true)) {
            $role = match ($type) {
                self::TYPE_RIDER_ITEM_INFO => 'rider',
                self::TYPE_OS_ITEM_INFO => 'os',
                default => 'admin',
            };
            $before = is_array($data['before'] ?? null) ? $data['before'] : null;
            $after = is_array($data['after'] ?? null) ? $data['after'] : null;
            if (! $this->hasItemFieldChanges($before, $after, $role)) {
                return null;
            }
        }

        $message = $this->composeMessage($type, $order, $data, $time, (string) ($history->history_message ?? ''));
        if ($message === null || $message === '') {
            return null;
        }

        $panel = $this->structuredPanel($type, $order, $data);
        $hasPanel = ($panel['action'] ?? null)
            || ($panel['reason'] ?? null)
            || ! empty($panel['action_parts'])
            || ! empty($panel['items'])
            || ! empty($panel['photos']);

        // Only parse structured "details" blobs — never prose history_message sentences
        // (timestamps like 14:46 were being split into fake Item cards).
        if (! $hasPanel && ! empty($data['details']) && $this->looksLikeStructuredDetails((string) $data['details'])) {
            $parsed = $this->parseLegacyDetails((string) $data['details']);
            $panel['action'] = $panel['action'] ?: ($parsed['action'] ?? null);
            $panel['reason'] = $panel['reason'] ?: ($parsed['reason'] ?? null);
            if (empty($panel['items'])) {
                $panel['items'] = $parsed['items'];
            }
            if (empty($panel['action_parts']) && ! empty($panel['action'])) {
                $panel['action_parts'] = preg_split('/\s*[·+]\s*/u', (string) $panel['action'], -1, PREG_SPLIT_NO_EMPTY) ?: [(string) $panel['action']];
            }
            $hasPanel = ($panel['action'] ?? null)
                || ($panel['reason'] ?? null)
                || ! empty($panel['action_parts'])
                || ! empty($panel['items'])
                || ! empty($panel['photos']);
        }

        // Clean summary: drop old parenthetical detail blobs from stored messages.
        $summary = preg_replace('/\s*\([^)]*(?:ItemValue|DeliAmount|လုပ်ဆောင်ချက်|ရေးထားသောအကြောင်းရင်း)[^)]*\)\s*$/u', '', $message) ?? $message;
        $summary = trim(preg_replace('/\s{2,}/u', ' ', $summary) ?? $summary);

        return [
            'type' => $type,
            'title' => $this->titleFor($type),
            'message' => $message,
            'summary' => $summary !== '' ? $summary : $message,
            'time' => $time,
            'icon' => $this->iconFor($type),
            'tone' => $this->toneFor($type),
            'sort_at' => Carbon::parse($at)->timestamp,
            'action' => $panel['action'] ?? null,
            'action_parts' => $panel['action_parts'] ?? [],
            'reason' => $panel['reason'] ?? null,
            'changes' => [],
            'items' => $panel['items'] ?? [],
            'photos' => $panel['photos'] ?? [],
        ];
    }

    /**
     * Structured detail panel for the audit UI (action / reason / items).
     *
     * @return array{action:?string,action_parts:array<int,string>,reason:?string,changes:array,items:array<int,array{code:string,chips:array<int,array{label:string,value:string}>}>}
     */
    protected function structuredPanel(string $type, Order $order, array $data): array
    {
        $action = null;
        $actionParts = [];
        if (! empty($data['action']) && is_string($data['action']) && ! in_array($data['action'], ['admin_item_info', 'Item Update'], true)) {
            $action = $data['action'];
            $actionParts = preg_split('/\s*[·+]\s*/u', $action, -1, PREG_SPLIT_NO_EMPTY) ?: [$action];
        }

        $reason = null;
        if (in_array($type, ['pickup_error', 'cancelled', self::TYPE_USER_CHOICE, self::TYPE_DELIVERY_ITEM_STATUS], true)) {
            $reason = trim((string) ($data['remark'] ?? $data['reason'] ?? ''));
            if ($reason === '' && in_array($type, ['pickup_error', 'cancelled', self::TYPE_USER_CHOICE], true)) {
                $reason = trim((string) ($order->reason ?? ''));
            }
            if ($reason === '') {
                $reason = null;
            }
        }

        $before = is_array($data['before'] ?? null) ? $data['before'] : null;
        $after = is_array($data['after'] ?? null) ? $data['after'] : null;
        $photos = [];

        if (in_array($type, [
            self::TYPE_ADMIN_ITEM_INFO,
            self::TYPE_RIDER_ITEM_INFO,
            self::TYPE_OS_ITEM_INFO,
        ], true)) {
            $role = match ($type) {
                self::TYPE_RIDER_ITEM_INFO => 'rider',
                self::TYPE_OS_ITEM_INFO => 'os',
                default => 'admin',
            };

            if (! $after) {
                $after = $this->resolveItemSnapshotFromData($order, $data);
            }

            if ($after && $before) {
                $actionParts = $this->itemActionParts($before, $after, $role);
                $action = $actionParts !== [] ? implode(' · ', $actionParts) : null;
            }
            // No before snapshot = cannot prove a change; leave empty (entry is filtered out).
        } elseif ($type === self::TYPE_DELI_AMOUNT) {
            $code = (string) ($data['item_code'] ?? ('#'.($data['item_id'] ?? '-')));
            $from = (string) ($data['from'] ?? $this->formatMoney((float) ($data['old_value'] ?? 0)));
            $to = (string) ($data['to'] ?? $this->formatMoney((float) ($data['new_value'] ?? 0)));
            $sizeLabel = trim((string) ($data['size'] ?? ''));
            if ($sizeLabel === '' || $sizeLabel === '—') {
                $sizeWeight = (int) ($data['weight'] ?? (is_array($data['after'] ?? null) ? ($data['after']['weight'] ?? 0) : 0));
                if ($sizeWeight <= 0) {
                    $snap = $this->resolveItemSnapshotFromData($order, $data);
                    $sizeWeight = (int) ($snap['weight'] ?? 0);
                }
                $sizeLabel = $this->friendlySizeLabel($sizeWeight);
            }

            if (! empty($data['action_parts']) && is_array($data['action_parts'])) {
                $actionParts = array_values(array_filter(array_map('strval', $data['action_parts'])));
            } else {
                $actionParts = [
                    __('message.order').' #'.$order->id,
                    $this->t('dispatch_audit_field_item').' — '.$code,
                ];
                if ($sizeLabel !== '' && $sizeLabel !== '—') {
                    $actionParts[] = $this->t('dispatch_audit_field_size').' — '.$sizeLabel;
                }
                $actionParts[] = $this->t('dispatch_audit_field_deli_amount').' — '.$from.' → '.$to;
            }

            // Ensure Size chip exists for older logs.
            $hasSizePart = collect($actionParts)->contains(
                fn ($p) => str_contains((string) $p, $this->t('dispatch_audit_field_size'))
                    || str_contains((string) $p, 'Size')
                    || str_contains((string) $p, 'အရွယ်အစား')
            );
            if (! $hasSizePart && $sizeLabel !== '' && $sizeLabel !== '—') {
                $insertAt = 2;
                if (count($actionParts) < 2) {
                    $insertAt = count($actionParts);
                }
                array_splice(
                    $actionParts,
                    $insertAt,
                    0,
                    [$this->t('dispatch_audit_field_size').' — '.$sizeLabel]
                );
            }

            $action = implode(' · ', $actionParts);
        } elseif ($type === self::TYPE_PHOTO_UPLOADED) {
            $code = (string) ($data['item_code'] ?? ('#'.($data['item_id'] ?? '-')));
            $actionParts = [$this->t('dispatch_audit_field_item').' — '.$code];
            $action = implode(' · ', $actionParts);
            $photos = $this->photosFromData($order, $data);
        } elseif ($type === self::TYPE_DELIVERY_ITEM_STATUS) {
            $photos = $this->photosFromData($order, $data);
            if ($photos === [] && (string) ($data['to_status'] ?? '') === 'pending') {
                $photos = $this->pendingPhotosFromItemData($order, $data);
            }
        } elseif ($type === self::TYPE_ITEM_CREATED) {
            if (! $after) {
                $after = $this->resolveItemSnapshotFromData($order, $data);
            }
            $role = (string) ($data['actor_role'] ?? 'admin');
            $fieldRole = match ($role) {
                'rider' => 'rider',
                'os', 'client' => 'os',
                default => 'admin',
            };
            if ($after) {
                $actionParts = $this->itemActionParts(null, $after, $fieldRole);
            }
            $base = (string) ($data['action'] ?? 'Item ထည့်');
            array_unshift($actionParts, $base);
            $action = implode(' · ', $actionParts);
        } elseif ($type === self::TYPE_ITEM_DELETED) {
            $base = (string) ($data['action'] ?? 'Item ဖျက်');
            $code = (string) ($data['item_code'] ?? ('#'.($data['item_id'] ?? '')));
            $actionParts = $code !== '' && $code !== '#' ? [$base, 'Item: '.$code] : [$base];
            $action = implode(' · ', $actionParts);
        }

        $items = [];
        if (in_array($type, [self::TYPE_PICKUP_COMPLETED, 'courier_picked_up'], true)) {
            $cards = is_array($data['items'] ?? null) && $data['items'] !== []
                ? $data['items']
                : $this->buildItemCards($order);
            $actionParts = $this->actionPartsFromItemCards($cards);
            $action = $actionParts !== [] ? implode(' · ', $actionParts) : null;
            $items = [];
            $photos = $this->photosFromOrderItems($order);
        }

        if (($action === null || $action === '') && ! empty($data['details']) && $this->looksLikeStructuredDetails((string) $data['details'])) {
            $parsed = $this->parseLegacyDetails((string) $data['details']);
            if (! empty($parsed['action'])) {
                $action = $parsed['action'];
                $actionParts = preg_split('/\s*[·+]\s*/u', $action, -1, PREG_SPLIT_NO_EMPTY) ?: [$action];
            }
            if (! $reason && ! empty($parsed['reason'])) {
                $reason = $parsed['reason'];
            }
        }

        return [
            'action' => $action ?: null,
            'action_parts' => $actionParts,
            'reason' => ($reason !== null && $reason !== '') ? $reason : null,
            'changes' => [],
            'items' => $items,
            'photos' => $photos,
        ];
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array<int, array{label:string,from:?string,to:?string,value:?string}>
     */
    protected function buildChangeRows(array $before, array $after): array
    {
        $rows = [];
        $map = [
            'item_value' => 'ItemValue',
            'deli_amount' => 'DeliAmount',
            'weight' => 'Size',
            'pickup_pay_mode' => 'Pay',
            'customer_name' => 'Customer',
            'customer_phone' => 'Phone',
            'customer_address' => 'Address',
            'remark' => 'Remark',
        ];

        foreach ($map as $key => $label) {
            if ($key === 'weight') {
                $from = $this->formatSizeLabel((int) ($before[$key] ?? 0));
                $to = $this->formatSizeLabel((int) ($after[$key] ?? 0));
            } elseif ($key === 'pickup_pay_mode') {
                $from = $this->formatPayModeLabel($before);
                $to = $this->formatPayModeLabel($after);
            } elseif (in_array($key, ['item_value', 'deli_amount'], true)) {
                $from = $this->formatMoney((float) ($before[$key] ?? 0));
                $to = $this->formatMoney((float) ($after[$key] ?? 0));
            } else {
                $from = trim((string) ($before[$key] ?? ''));
                $to = trim((string) ($after[$key] ?? ''));
                $from = $from !== '' ? $from : '—';
                $to = $to !== '' ? $to : '—';
            }

            if ((string) $from === (string) $to) {
                continue;
            }

            $rows[] = [
                'label' => $label,
                'from' => $from,
                'to' => $to,
                'value' => null,
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $snap
     * @return array<int, array{label:string,from:?string,to:?string,value:?string}>
     */
    protected function buildSnapshotRows(array $snap): array
    {
        $rows = [];
        if ((float) ($snap['item_value'] ?? 0) > 0) {
            $rows[] = ['label' => 'ItemValue', 'from' => null, 'to' => null, 'value' => $this->formatMoney((float) $snap['item_value'])];
        }
        if ((float) ($snap['deli_amount'] ?? 0) > 0) {
            $rows[] = ['label' => 'DeliAmount', 'from' => null, 'to' => null, 'value' => $this->formatMoney((float) $snap['deli_amount'])];
        }
        if ((int) ($snap['weight'] ?? 0) > 0) {
            $rows[] = ['label' => 'Size', 'from' => null, 'to' => null, 'value' => $this->formatSizeLabel((int) $snap['weight'])];
        }
        $pay = $this->formatPayModeLabel($snap);
        if ($pay !== '—') {
            $rows[] = ['label' => 'Pay', 'from' => null, 'to' => null, 'value' => $pay];
        }
        foreach (['customer_name' => 'Customer', 'customer_phone' => 'Phone', 'customer_address' => 'Address', 'remark' => 'Remark'] as $key => $label) {
            $val = trim((string) ($snap[$key] ?? ''));
            if ($val !== '') {
                $rows[] = ['label' => $label, 'from' => null, 'to' => null, 'value' => $val];
            }
        }

        return $rows;
    }

    /**
     * @return array<int, array{code:string,chips:array<int,array{label:string,value:string}>}>
     */
    public function buildItemCards(Order $order): array
    {
        $order->loadMissing('dispatchItems');
        $cards = [];
        foreach ($order->dispatchItems as $item) {
            $snap = $this->itemSnapshot($item);
            $chips = [];
            if ((float) $snap['item_value'] > 0) {
                $chips[] = ['label' => 'ItemValue', 'value' => $this->formatMoney((float) $snap['item_value'])];
            }
            if ((float) $snap['deli_amount'] > 0) {
                $chips[] = ['label' => 'DeliAmount', 'value' => $this->formatMoney((float) $snap['deli_amount'])];
            }
            if ((int) $snap['weight'] > 0) {
                $chips[] = ['label' => 'Size', 'value' => $this->formatSizeLabel((int) $snap['weight'])];
            }
            $pay = $this->formatPayModeLabel($snap);
            if ($pay !== '—') {
                $chips[] = ['label' => 'Pay', 'value' => $pay];
            }
            $cards[] = [
                'code' => (string) ($item->code ?: ('#'.$item->id)),
                'chips' => $chips,
            ];
        }

        return $cards;
    }

    /**
     * True when a details string is a structured blob we can safely parse,
     * not a full Burmese sentence that may contain timestamps like 14:46.
     */
    protected function looksLikeStructuredDetails(string $details): bool
    {
        $raw = trim($details);
        if ($raw === '') {
            return false;
        }

        return str_contains($raw, 'ItemValue')
            || str_contains($raw, 'DeliAmount')
            || str_contains($raw, 'လုပ်ဆောင်ချက်:')
            || str_contains($raw, 'ရေးထားသောအကြောင်းရင်း:')
            || (bool) preg_match('/Item\s+\S+:\s*(ItemValue|DeliAmount|Size|Pay|Os Pay|Customer Pay|Pay Done)/u', $raw);
    }

    /**
     * @return array{action:?string,reason:?string,changes:array,items:array}
     */
    protected function parseLegacyDetails(string $details): array
    {
        $raw = trim($details);
        $raw = trim($raw, " \t\n\r\0\x0B()");
        $action = null;
        $reason = null;
        $changes = [];
        $items = [];

        if (preg_match('/ရေးထားသောအကြောင်းရင်း:\s*(.+)$/u', $raw, $m)) {
            $reason = trim($m[1]);
        }
        if (preg_match('/အကြောင်းရင်းရေးထားသည်\s*[—\-]\s*(.+)$/u', $raw, $m)) {
            $reason = trim($m[1]);
        }

        if (preg_match('/လုပ်ဆောင်ချက်:\s*([^·|]+)/u', $raw, $m)) {
            $action = trim($m[1]);
        }

        // Item cards: "Item CODE: a, b | Item CODE2: ..."
        // Only accept structured chip values — ignore prose / timestamps (14:46).
        if (preg_match_all('/Item\s+([^:|]+):\s*([^|]+)(?:\||$)/u', $raw, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $chipRaw = trim($match[2]);
                if (! preg_match('/(ItemValue|DeliAmount|Size\(|\bSize\b|Os Pay|Customer Pay|Pay Done|\bPay\b)/u', $chipRaw)) {
                    continue;
                }

                $chips = [];
                $parts = preg_split('/,\s*/', $chipRaw) ?: [];
                foreach ($parts as $part) {
                    $part = trim($part);
                    if ($part === '') {
                        continue;
                    }
                    if (preg_match('/^(ItemValue|DeliAmount)\s+(.+)$/u', $part, $pm)) {
                        $chips[] = ['label' => $pm[1], 'value' => trim($pm[2])];
                    } elseif (preg_match('/^Size\(\d+\)$/u', $part) || preg_match('/^Size:\s*(.+)$/u', $part, $sm)) {
                        $chips[] = ['label' => 'Size', 'value' => isset($sm[1]) ? trim($sm[1]) : $part];
                    } elseif (in_array($part, ['Os Pay', 'Customer Pay', 'Pay Done'], true)) {
                        $chips[] = ['label' => 'Pay', 'value' => $part];
                    } elseif (preg_match('/^Pay:\s*(.+)$/u', $part, $pm)) {
                        $chips[] = ['label' => 'Pay', 'value' => trim($pm[1])];
                    } else {
                        $chips[] = ['label' => 'Info', 'value' => $part];
                    }
                }
                if ($chips !== []) {
                    $items[] = ['code' => trim($match[1]), 'chips' => $chips];
                }
            }
        }

        // Change rows: "Label: old → new"
        if ($items === [] && preg_match_all('/(ItemValue|DeliAmount|Size|Pay|Customer|Phone|Address|Remark):\s*([^·]+)/u', $raw, $cm, PREG_SET_ORDER)) {
            foreach ($cm as $row) {
                $label = $row[1];
                $value = trim($row[2]);
                if (str_contains($value, '→')) {
                    [$from, $to] = array_map('trim', explode('→', $value, 2));
                    $changes[] = ['label' => $label, 'from' => $from, 'to' => $to, 'value' => null];
                } else {
                    $changes[] = ['label' => $label, 'from' => null, 'to' => null, 'value' => $value];
                }
            }
        }

        return compact('action', 'reason', 'changes', 'items');
    }

    protected function composeMessage(string $type, Order $order, array $data, string $time, string $fallback): ?string
    {
        $clientName = $this->resolveName($data['client_name'] ?? null, optional($order->client)->name);
        $adminName = $this->resolveName(
            $data['admin_name'] ?? null,
            (($data['actor_type'] ?? '') === 'admin') ? ($data['actor_name'] ?? null) : null,
            $this->t('dispatch_audit_account_admin')
        );
        $riderName = $this->resolveName(
            $data['rider_name'] ?? null,
            $data['delivery_man_name'] ?? null,
            (($data['actor_type'] ?? '') === 'delivery_man') ? ($data['actor_name'] ?? null) : null,
            optional($order->delivery_man)->name,
            $this->lastKnownRiderName($order),
            $this->t('dispatch_audit_account_rider')
        );

        return match ($type) {
            self::TYPE_ORDER_CREATED, 'create', 'draft' => $this->t('dispatch_audit_order_created', [
                'client' => $clientName,
                'order' => $order->id,
            ]),
            self::TYPE_PICKUP_RIDER_ASSIGNED, 'courier_assigned' => $this->t('dispatch_audit_pickup_rider_assigned', [
                'admin' => $adminName,
                'order' => $order->id,
                'rider' => $riderName,
            ]),
            self::TYPE_ADMIN_ITEM_INFO => $this->t('dispatch_audit_admin_item_info', [
                'admin' => $adminName,
                'order' => $order->id,
                'item' => $data['item_code'] ?? ('#' . ($data['item_id'] ?? '-')),
            ]),
            self::TYPE_RIDER_ITEM_INFO => $this->t('dispatch_audit_rider_item_info', [
                'rider' => $riderName,
                'order' => $order->id,
                'item' => $data['item_code'] ?? ('#' . ($data['item_id'] ?? '-')),
            ]),
            self::TYPE_PICKUP_COMPLETED, 'courier_picked_up' => $this->t('dispatch_audit_pickup_completed', [
                'rider' => $riderName,
                'order' => $order->id,
            ]),
            self::TYPE_DELIVERY_RIDER_ASSIGNED => $this->t('dispatch_audit_delivery_rider_assigned', [
                'admin' => $adminName,
                'order' => $order->id,
                'rider' => $riderName,
                'count' => $data['item_count'] ?? 1,
            ]),
            self::TYPE_RESTORED => $this->restoredMessage($order, $data, $clientName, $adminName, $time),
            self::TYPE_USER_CHOICE => $this->t('dispatch_audit_user_choice', [
                'client' => $clientName,
                'order' => $order->id,
                'choice' => $data['action']
                    ?? pickupErrorChoiceLabel((string) ($data['choice'] ?? ''))
                    ?? ((string) ($data['choice'] ?? '-')),
            ]),
            self::TYPE_ITEM_CREATED => $this->itemCreatedMessage($order, $data, $clientName, $adminName, $riderName),
            self::TYPE_ITEM_DELETED => $this->itemDeletedMessage($order, $data, $clientName, $adminName),
            self::TYPE_OS_ITEM_INFO => $this->t('dispatch_audit_os_item_info', [
                'client' => $clientName,
                'order' => $order->id,
                'item' => $data['item_code'] ?? ('#'.($data['item_id'] ?? '-')),
            ]),
            self::TYPE_DELI_AMOUNT => $this->t('dispatch_audit_deli_amount_changed', [
                'actor' => $this->resolveName(
                    $data['admin_name'] ?? null,
                    $data['rider_name'] ?? null,
                    $data['client_name'] ?? null,
                    $data['actor_name'] ?? null,
                    $adminName,
                    $riderName,
                    $clientName
                ),
                'order' => $order->id,
                'item' => $data['item_code'] ?? ('#'.($data['item_id'] ?? '-')),
                'from' => $data['from'] ?? $this->formatMoney((float) ($data['old_value'] ?? 0)),
                'to' => $data['to'] ?? $this->formatMoney((float) ($data['new_value'] ?? 0)),
            ]),
            self::TYPE_PHOTO_UPLOADED => $this->t('dispatch_audit_photo_uploaded', [
                'rider' => $riderName,
                'order' => $order->id,
                'item' => $data['item_code'] ?? ('#'.($data['item_id'] ?? '-')),
            ]),
            self::TYPE_PRE_PICKUP_MOVED => $this->t('dispatch_audit_pre_pickup_moved', [
                'admin' => $adminName,
                'order' => $order->id,
            ]),
            self::TYPE_MOVED_TO_ASSIGN_100 => $this->t('dispatch_audit_moved_to_assign_100', [
                'admin' => $adminName,
                'order' => $order->id,
                'count' => $data['item_count'] ?? 1,
            ]),
            'pickup_error' => $this->t('dispatch_audit_pickup_error', [
                'rider' => $riderName,
                'order' => $order->id,
            ]),
            'cancelled' => $this->cancelledMessage($order, $clientName, $time, $data),
            'courier_departed' => $this->t('dispatch_audit_on_way', [
                'rider' => $riderName,
                'order' => $order->id,
            ]),
            'completed' => $this->t('dispatch_audit_delivered', [
                'rider' => $riderName,
                'order' => $order->id,
            ]),
            default => $fallback !== '' ? $fallback : null,
        };
    }

    protected function itemCreatedMessage(
        Order $order,
        array $data,
        string $clientName,
        string $adminName,
        string $riderName
    ): string {
        $item = $data['item_code'] ?? ('#'.($data['item_id'] ?? '-'));
        $role = (string) ($data['actor_role'] ?? 'admin');

        return match ($role) {
            'rider' => $this->t('dispatch_audit_rider_item_created', [
                'rider' => $riderName,
                'order' => $order->id,
                'item' => $item,
            ]),
            'client', 'os' => $this->t('dispatch_audit_os_item_created', [
                'client' => $clientName,
                'order' => $order->id,
                'item' => $item,
            ]),
            default => $this->t('dispatch_audit_admin_item_created', [
                'admin' => $adminName,
                'order' => $order->id,
                'item' => $item,
            ]),
        };
    }

    protected function itemDeletedMessage(
        Order $order,
        array $data,
        string $clientName,
        string $adminName
    ): string {
        $item = $data['item_code'] ?? ('#'.($data['item_id'] ?? '-'));
        $role = (string) ($data['actor_role'] ?? 'admin');

        if (in_array($role, ['client', 'os'], true)) {
            return $this->t('dispatch_audit_os_item_deleted', [
                'client' => $clientName,
                'order' => $order->id,
                'item' => $item,
            ]);
        }

        return $this->t('dispatch_audit_admin_item_deleted', [
            'admin' => $adminName,
            'order' => $order->id,
            'item' => $item,
        ]);
    }

    protected function restoredMessage(Order $order, array $data, string $clientName, string $adminName, string $time): string
    {
        $by = (string) ($data['restored_by'] ?? '');
        $actorType = (string) ($data['actor_type'] ?? '');

        $isAdmin = $by === 'admin'
            || in_array($actorType, ['admin', 'demo_admin'], true)
            || (! empty($data['admin_name']) && $by !== 'os');

        if ($isAdmin) {
            return $this->t('dispatch_audit_restored_admin', [
                'admin' => $this->resolveName($data['admin_name'] ?? null, $data['actor_name'] ?? null, $adminName),
                'order' => $order->id,
            ]);
        }

        return $this->t('dispatch_audit_restored_os', [
            'client' => $this->resolveName($data['client_name'] ?? null, $data['actor_name'] ?? null, $clientName),
            'order' => $order->id,
        ]);
    }

    protected function cancelledMessage(Order $order, string $clientName, string $time, array $data = []): string
    {
        $choice = (string) ($order->pickup_error_choice ?? '');
        $choiceLabel = pickupErrorChoiceLabel($choice);

        if ($choiceLabel && in_array($choice, ['cancel', 'express'], true)) {
            return $this->t('dispatch_audit_cancelled_choice', [
                'client' => $clientName,
                'order' => $order->id,
                'choice' => $choiceLabel,
            ]);
        }

        return $this->t('dispatch_audit_cancelled', [
            'order' => $order->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function itemSnapshot(DispatchOrderItem $item): array
    {
        return [
            'item_value' => (float) ($item->item_value ?? 0),
            'deli_amount' => (float) ($item->deli_amount ?? 0),
            'weight' => (int) ($item->weight ?? 0),
            'pickup_pay_mode' => (string) ($item->pickup_pay_mode ?? ''),
            'credit_to' => (string) ($item->credit_to ?? 'customer'),
            'os_paid' => (float) ($item->os_paid ?? 0),
            'photo_id' => (int) ($item->photo_id ?? 0),
            'customer_name' => trim((string) ($item->customer_name ?? '')),
            'customer_phone' => trim((string) ($item->customer_phone ?? '')),
            'customer_address' => trim((string) ($item->customer_address ?? '')),
            'remark' => trim(stripAutoOrderRemarkTip($item->remark ?? null)),
        ];
    }

    protected function detailsFromData(array $data): string
    {
        $details = trim((string) ($data['details'] ?? ''));
        if ($details !== '') {
            return $details;
        }

        $after = is_array($data['after'] ?? null) ? $data['after'] : null;
        $before = is_array($data['before'] ?? null) ? $data['before'] : null;
        if ($after) {
            return $this->formatItemDetails($after, $before, $data['action'] ?? null);
        }

        return '';
    }

    protected function inferRiderActionLabel(?array $before, array $after): string
    {
        return implode(' · ', $this->itemActionParts($before, $after, 'rider')) ?: 'Rider Size / Pay Update';
    }

    protected function inferAdminActionLabel(?array $before, array $after): string
    {
        return implode(' · ', $this->itemActionParts($before, $after, 'admin')) ?: 'Admin Item Info ဖြည့်/ပြင်';
    }

    protected function inferOsActionLabel(?array $before, array $after): string
    {
        return implode(' · ', $this->itemActionParts($before, $after, 'os')) ?: 'OS Item Update';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    protected function resolveItemSnapshotFromData(Order $order, array $data): ?array
    {
        if (is_array($data['after'] ?? null)) {
            return $data['after'];
        }

        $itemId = (int) ($data['item_id'] ?? 0);
        $itemCode = trim((string) ($data['item_code'] ?? ''));

        $order->loadMissing('dispatchItems');
        $item = null;
        if ($itemId > 0) {
            $item = $order->dispatchItems->firstWhere('id', $itemId);
            if (! $item) {
                $item = DispatchOrderItem::query()->find($itemId);
            }
        }
        if (! $item && $itemCode !== '') {
            $item = $order->dispatchItems->firstWhere('code', $itemCode);
            if (! $item) {
                $item = DispatchOrderItem::query()
                    ->where('order_id', $order->id)
                    ->where('code', $itemCode)
                    ->first();
            }
        }

        if ($item) {
            return $this->itemSnapshot($item);
        }

        return is_array($data['before'] ?? null) ? $data['before'] : null;
    }

    /**
     * Convert item cards into action pills matching Rider Item Update style.
     *
     * @param  array<int, array{code?:string,chips?:array<int,array{label:string,value:string}>}>  $cards
     * @return array<int, string>
     */
    protected function actionPartsFromItemCards(array $cards): array
    {
        $parts = [];
        $labelMap = [
            'ItemValue' => $this->t('dispatch_audit_field_item_value'),
            'DeliAmount' => $this->t('dispatch_audit_field_deli_amount'),
            'Size' => $this->t('dispatch_audit_field_size'),
            'Pay' => $this->t('dispatch_audit_field_pay'),
            'Customer' => $this->t('dispatch_audit_field_customer'),
            'Phone' => $this->t('dispatch_audit_field_phone'),
            'Address' => $this->t('dispatch_audit_field_address'),
            'Remark' => $this->t('dispatch_audit_field_remark'),
        ];

        foreach ($cards as $card) {
            $code = trim((string) ($card['code'] ?? ''));
            if ($code !== '') {
                $parts[] = $this->t('dispatch_audit_field_item').' — '.$code;
            }

            foreach ($card['chips'] ?? [] as $chip) {
                $rawLabel = trim((string) ($chip['label'] ?? ''));
                $value = trim((string) ($chip['value'] ?? ''));
                if ($rawLabel === '' || $value === '') {
                    continue;
                }
                if ($rawLabel === 'Size' && preg_match('/Size\s*\((\d+)\)/', $value, $m)) {
                    $value = 'Size ('.$m[1].')';
                } elseif ($rawLabel === 'Size' && ctype_digit($value)) {
                    $value = 'Size ('.$value.')';
                } elseif ($rawLabel === 'Pay') {
                    $value = match ($value) {
                        'Os Pay' => $this->t('dispatch_audit_pay_os'),
                        'Pay Done' => $this->t('dispatch_audit_pay_done'),
                        'Customer Pay' => $this->t('dispatch_audit_pay_customer'),
                        default => $value,
                    };
                }
                $parts[] = ($labelMap[$rawLabel] ?? $rawLabel).' — '.$value;
            }
        }

        return $parts;
    }

    /**
     * @param  array<string, mixed>  $snap
     * @return array{code:string,chips:array<int,array{label:string,value:string}>}
     */
    protected function itemCardFromSnapshot(string $code, array $snap): array
    {
        $chips = [];
        if ((float) ($snap['item_value'] ?? 0) > 0) {
            $chips[] = ['label' => 'ItemValue', 'value' => $this->formatMoney((float) $snap['item_value'])];
        }
        if ((float) ($snap['deli_amount'] ?? 0) > 0) {
            $chips[] = ['label' => 'DeliAmount', 'value' => $this->formatMoney((float) $snap['deli_amount'])];
        }
        if ((int) ($snap['weight'] ?? 0) > 0) {
            $chips[] = ['label' => 'Size', 'value' => $this->formatSizeLabel((int) $snap['weight'])];
        }
        $pay = $this->formatPayModeLabel($snap);
        if ($pay !== '—') {
            $chips[] = ['label' => 'Pay', 'value' => $pay];
        }
        foreach (['customer_name' => 'Customer', 'customer_phone' => 'Phone', 'customer_address' => 'Address', 'remark' => 'Remark'] as $key => $label) {
            $val = trim((string) ($snap[$key] ?? ''));
            if ($val !== '') {
                $chips[] = ['label' => $label, 'value' => $val];
            }
        }

        return [
            'code' => $code !== '' ? $code : '-',
            'chips' => $chips,
        ];
    }

    /**
     * Exact field-level update labels for audit "လုပ်ဆောင်ချက်".
     *
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>  $after
     * @param  string  $role  admin|rider|os|all
     * @return array<int, string>
     */
    protected function hasItemFieldChanges(?array $before, ?array $after, string $role = 'all'): bool
    {
        if (! is_array($before) || ! is_array($after)) {
            return false;
        }

        return $this->itemActionParts($before, $after, $role) !== [];
    }

    protected function itemActionParts(?array $before, array $after, string $role = 'all'): array
    {
        $parts = [];
        $map = match ($role) {
            'admin' => [
                'customer_name' => $this->t('dispatch_audit_field_customer'),
                'customer_phone' => $this->t('dispatch_audit_field_phone'),
                'customer_address' => $this->t('dispatch_audit_field_address'),
                'item_value' => $this->t('dispatch_audit_field_item_value'),
                'deli_amount' => $this->t('dispatch_audit_field_deli_amount'),
                'pickup_pay_mode' => $this->t('dispatch_audit_field_pay'),
                'weight' => $this->t('dispatch_audit_field_size'),
                'remark' => $this->t('dispatch_audit_field_remark'),
            ],
            'rider' => [
                'weight' => $this->t('dispatch_audit_field_size'),
                'pickup_pay_mode' => $this->t('dispatch_audit_field_pay'),
                'item_value' => $this->t('dispatch_audit_field_item_value'),
                'deli_amount' => $this->t('dispatch_audit_field_deli_amount'),
                'photo_id' => $this->t('dispatch_audit_field_photo'),
            ],
            'os' => [
                'customer_name' => $this->t('dispatch_audit_field_customer'),
                'customer_phone' => $this->t('dispatch_audit_field_phone'),
                'customer_address' => $this->t('dispatch_audit_field_address'),
                'item_value' => $this->t('dispatch_audit_field_item_value'),
                'deli_amount' => $this->t('dispatch_audit_field_deli_amount'),
                'pickup_pay_mode' => $this->t('dispatch_audit_field_pay'),
                'remark' => $this->t('dispatch_audit_field_remark'),
            ],
            default => [
                'weight' => $this->t('dispatch_audit_field_size'),
                'pickup_pay_mode' => $this->t('dispatch_audit_field_pay'),
                'item_value' => $this->t('dispatch_audit_field_item_value'),
                'deli_amount' => $this->t('dispatch_audit_field_deli_amount'),
                'customer_name' => $this->t('dispatch_audit_field_customer'),
                'customer_phone' => $this->t('dispatch_audit_field_phone'),
                'customer_address' => $this->t('dispatch_audit_field_address'),
                'remark' => $this->t('dispatch_audit_field_remark'),
                'photo_id' => $this->t('dispatch_audit_field_photo'),
            ],
        };

        foreach ($map as $key => $label) {
            if ($key === 'photo_id') {
                $toId = (int) ($after[$key] ?? 0);
                $fromId = $before !== null ? (int) ($before[$key] ?? 0) : null;
                if ($before === null) {
                    if ($toId > 0) {
                        $parts[] = $label.' — '.$this->t('dispatch_audit_photo_added');
                    }
                    continue;
                }
                if ($fromId !== $toId && $toId > 0) {
                    $parts[] = $label.' — '.($fromId > 0
                        ? $this->t('dispatch_audit_photo_changed')
                        : $this->t('dispatch_audit_photo_added'));
                }
                continue;
            }

            if ($key === 'weight') {
                $to = $this->friendlySizeLabel((int) ($after[$key] ?? 0));
                $from = $before !== null ? $this->friendlySizeLabel((int) ($before[$key] ?? 0)) : null;
            } elseif ($key === 'pickup_pay_mode') {
                $to = $this->friendlyPayLabel($after);
                $from = $before !== null ? $this->friendlyPayLabel($before) : null;
            } elseif (in_array($key, ['item_value', 'deli_amount'], true)) {
                $to = $this->formatMoney((float) ($after[$key] ?? 0));
                $from = $before !== null ? $this->formatMoney((float) ($before[$key] ?? 0)) : null;
            } else {
                $to = trim((string) ($after[$key] ?? ''));
                $to = $to !== '' ? $to : '—';
                $from = $before !== null
                    ? (trim((string) ($before[$key] ?? '')) !== '' ? trim((string) $before[$key]) : '—')
                    : null;
            }

            if ($before === null) {
                if ($to === '—' || $to === '' || $to === '0') {
                    continue;
                }
                $parts[] = $label.' — '.$to;
                continue;
            }

            if ((string) $from === (string) $to) {
                continue;
            }

            $parts[] = $label.' — '.$from.' → '.$to;
        }

        return $parts;
    }

    protected function friendlySizeLabel(int $weight): string
    {
        if ($weight <= 0) {
            return '—';
        }

        return 'Size ('.$weight.')';
    }

    /**
     * @param  array<string, mixed>  $snap
     */
    protected function friendlyPayLabel(array $snap): string
    {
        $mode = (string) ($snap['pickup_pay_mode'] ?? '');
        if ($mode === 'os_pay' || (($snap['credit_to'] ?? '') === 'os' && (float) ($snap['os_paid'] ?? 0) <= 0)) {
            return $this->t('dispatch_audit_pay_os');
        }
        if ($mode === 'pay_done' || (float) ($snap['os_paid'] ?? 0) > 0) {
            return $this->t('dispatch_audit_pay_done');
        }
        if ($mode === 'customer_pay' || (float) ($snap['item_value'] ?? 0) > 0 || (float) ($snap['deli_amount'] ?? 0) > 0) {
            return $this->t('dispatch_audit_pay_customer');
        }

        return '—';
    }

    /**
     * @return array{photo_id:?int,photo_url:?string}
     */
    protected function resolvePhotoMeta(DispatchOrderItem $item): array
    {
        $photoId = (int) ($item->photo_id ?? 0);
        if ($photoId <= 0) {
            return ['photo_id' => null, 'photo_url' => null];
        }

        $item->loadMissing('photoMedia');
        $media = $item->photoMedia;
        $url = $media ? mediaPublicUrl($media) : null;

        return [
            'photo_id' => $photoId,
            'photo_url' => $url,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array{url:string,label:string}>
     */
    protected function photosFromData(Order $order, array $data): array
    {
        if (is_array($data['photos'] ?? null) && $data['photos'] !== []) {
            return array_values(array_filter($data['photos'], fn ($p) => ! empty($p['url'])));
        }

        $url = trim((string) ($data['photo_url'] ?? ''));
        $label = (string) ($data['item_code'] ?? ('#'.($data['item_id'] ?? '')));
        if ($url !== '') {
            return [['url' => $url, 'label' => $label]];
        }

        $itemId = (int) ($data['item_id'] ?? 0);
        $order->loadMissing('dispatchItems.photoMedia');
        $item = $itemId > 0
            ? $order->dispatchItems->firstWhere('id', $itemId)
            : null;
        if (! $item) {
            return [];
        }
        $meta = $this->resolvePhotoMeta($item);
        if (empty($meta['photo_url'])) {
            return [];
        }

        return [[
            'url' => $meta['photo_url'],
            'label' => (string) ($item->code ?: ('#'.$item->id)),
        ]];
    }

    /**
     * @return array<int, array{url:string,label:string}>
     */
    protected function photosFromOrderItems(Order $order): array
    {
        $order->loadMissing('dispatchItems.photoMedia');
        $photos = [];
        foreach ($order->dispatchItems as $item) {
            $meta = $this->resolvePhotoMeta($item);
            if (empty($meta['photo_url'])) {
                continue;
            }
            $photos[] = [
                'url' => $meta['photo_url'],
                'label' => (string) ($item->code ?: ('#'.$item->id)),
            ];
        }

        return $photos;
    }

    /**
     * @param  array<string, mixed>  $after
     * @param  array<string, mixed>|null  $before
     */
    protected function formatItemDetails(array $after, ?array $before = null, ?string $action = null): string
    {
        $parts = [];
        if ($action) {
            $parts[] = 'လုပ်ဆောင်ချက်: '.$action;
        }

        $fields = [
            'item_value' => 'ItemValue',
            'deli_amount' => 'DeliAmount',
            'weight' => 'Size',
            'pickup_pay_mode' => 'Pay',
            'customer_name' => 'Customer',
            'customer_phone' => 'Phone',
            'customer_address' => 'Address',
            'remark' => 'Remark',
        ];

        foreach ($fields as $key => $label) {
            $newRaw = $after[$key] ?? null;
            $oldRaw = $before[$key] ?? null;

            if ($key === 'weight') {
                $newVal = $this->formatSizeLabel((int) $newRaw);
                $oldVal = $before ? $this->formatSizeLabel((int) $oldRaw) : null;
            } elseif ($key === 'pickup_pay_mode') {
                $newVal = $this->formatPayModeLabel($after);
                $oldVal = $before ? $this->formatPayModeLabel($before) : null;
            } elseif (in_array($key, ['item_value', 'deli_amount'], true)) {
                $newVal = $this->formatMoney((float) $newRaw);
                $oldVal = $before !== null ? $this->formatMoney((float) $oldRaw) : null;
            } else {
                $newVal = trim((string) $newRaw);
                $oldVal = $before !== null ? trim((string) $oldRaw) : null;
            }

            if ($before !== null) {
                if ((string) $oldVal === (string) $newVal) {
                    continue;
                }
                $parts[] = $label.': '.($oldVal !== '' && $oldVal !== null ? $oldVal : '—').' → '.($newVal !== '' ? $newVal : '—');
            } else {
                if ($newVal === '' || $newVal === null || $newVal === '—' || $newVal === '0') {
                    continue;
                }
                $parts[] = $label.': '.$newVal;
            }
        }

        // Always show final money/size snapshot when we have a before-diff that was empty of money.
        if ($before !== null && ! $this->detailsHasMoneyOrSize($parts)) {
            $snapshot = [];
            if ((float) ($after['item_value'] ?? 0) > 0) {
                $snapshot[] = 'ItemValue: '.$this->formatMoney((float) $after['item_value']);
            }
            if ((float) ($after['deli_amount'] ?? 0) > 0) {
                $snapshot[] = 'DeliAmount: '.$this->formatMoney((float) $after['deli_amount']);
            }
            if ((int) ($after['weight'] ?? 0) > 0) {
                $snapshot[] = 'Size: '.$this->formatSizeLabel((int) $after['weight']);
            }
            $pay = $this->formatPayModeLabel($after);
            if ($pay !== '—') {
                $snapshot[] = 'Pay: '.$pay;
            }
            $parts = array_merge($parts, $snapshot);
        }

        if ($parts === []) {
            return '';
        }

        return '('.implode(' · ', $parts).')';
    }

    protected function detailsHasMoneyOrSize(array $parts): bool
    {
        foreach ($parts as $part) {
            if (str_contains($part, 'ItemValue') || str_contains($part, 'DeliAmount') || str_contains($part, 'Size')) {
                return true;
            }
        }

        return false;
    }

    protected function formatPickupCompletedDetails(Order $order): string
    {
        $order->loadMissing('dispatchItems');
        $lines = [];
        foreach ($order->dispatchItems as $item) {
            $snap = $this->itemSnapshot($item);
            $bits = [];
            $code = $item->code ?: ('#'.$item->id);
            if ((float) $snap['item_value'] > 0) {
                $bits[] = 'ItemValue '.$this->formatMoney((float) $snap['item_value']);
            }
            if ((float) $snap['deli_amount'] > 0) {
                $bits[] = 'DeliAmount '.$this->formatMoney((float) $snap['deli_amount']);
            }
            if ((int) $snap['weight'] > 0) {
                $bits[] = $this->formatSizeLabel((int) $snap['weight']);
            }
            $pay = $this->formatPayModeLabel($snap);
            if ($pay !== '—') {
                $bits[] = $pay;
            }
            $lines[] = $bits !== []
                ? 'Item '.$code.': '.implode(', ', $bits)
                : 'Item '.$code;
        }

        if ($lines === []) {
            return '(လုပ်ဆောင်ချက်: Pick Up Completed နှိပ်)';
        }

        return '(လုပ်ဆောင်ချက်: Pick Up Completed နှိပ် · '.implode(' | ', $lines).')';
    }

    protected function formatSizeLabel(int $weight): string
    {
        if ($weight <= 0) {
            return '—';
        }

        return function_exists('formatDispatchItemSize')
            ? formatDispatchItemSize($weight)
            : 'Size('.$weight.')';
    }

    /**
     * @param  array<string, mixed>  $snap
     */
    protected function formatPayModeLabel(array $snap): string
    {
        $mode = (string) ($snap['pickup_pay_mode'] ?? '');
        if ($mode === 'os_pay') {
            return 'Os Pay';
        }
        if ($mode === 'pay_done') {
            return 'Os Paid';
        }
        if ($mode === 'customer_pay') {
            return 'Cust Pay';
        }
        if ((float) ($snap['os_paid'] ?? 0) > 0) {
            return 'Os Paid';
        }
        if (($snap['credit_to'] ?? 'customer') === 'os') {
            return 'Os Pay';
        }

        return ((float) ($snap['item_value'] ?? 0) > 0 || (float) ($snap['deli_amount'] ?? 0) > 0)
            ? 'Cust Pay'
            : '—';
    }

    protected function formatMoney(float $amount): string
    {
        if (abs($amount - round($amount)) < 0.001) {
            return number_format($amount, 0, '.', ',');
        }

        return number_format($amount, 2, '.', ',');
    }

    protected function resolveName(?string ...$candidates): string
    {
        foreach ($candidates as $name) {
            $name = trim((string) $name);
            if ($name !== '' && $name !== '-') {
                return $name;
            }
        }

        return '-';
    }

    protected function lastKnownRiderName(Order $order): ?string
    {
        foreach ($order->orderHistoryasc as $history) {
            $data = is_array($history->history_data) ? $history->history_data : [];
            $name = $data['rider_name'] ?? $data['delivery_man_name'] ?? null;
            if (is_string($name) && trim($name) !== '') {
                return trim($name);
            }
        }

        return null;
    }

    protected function titleFor(string $type): string
    {
        return match ($type) {
            self::TYPE_ORDER_CREATED, 'create', 'draft' => $this->t('dispatch_audit_title_order_created'),
            self::TYPE_PICKUP_RIDER_ASSIGNED, 'courier_assigned' => $this->t('dispatch_audit_title_pickup_rider_assigned'),
            self::TYPE_ADMIN_ITEM_INFO => $this->t('dispatch_audit_title_admin_item_info'),
            self::TYPE_RIDER_ITEM_INFO => $this->t('dispatch_audit_title_rider_item_info'),
            self::TYPE_PICKUP_COMPLETED, 'courier_picked_up' => $this->t('dispatch_audit_title_pickup_completed'),
            self::TYPE_DELIVERY_RIDER_ASSIGNED => $this->t('dispatch_audit_title_delivery_rider_assigned'),
            self::TYPE_RESTORED => $this->t('dispatch_audit_title_restored'),
            self::TYPE_USER_CHOICE => $this->t('dispatch_audit_title_user_choice'),
            self::TYPE_ITEM_CREATED => $this->t('dispatch_audit_title_item_created'),
            self::TYPE_ITEM_DELETED => $this->t('dispatch_audit_title_item_deleted'),
            self::TYPE_OS_ITEM_INFO => $this->t('dispatch_audit_title_os_item_info'),
            self::TYPE_DELI_AMOUNT => $this->t('dispatch_audit_title_deli_amount'),
            self::TYPE_PHOTO_UPLOADED => $this->t('dispatch_audit_title_photo_uploaded'),
            self::TYPE_PRE_PICKUP_MOVED => $this->t('dispatch_audit_title_pre_pickup_moved'),
            self::TYPE_MOVED_TO_ASSIGN_100 => $this->t('dispatch_audit_title_moved_to_assign_100'),
            self::TYPE_DELIVERY_ITEM_STATUS => $this->t('dispatch_audit_title_delivery_item_status'),
            'courier_departed' => __('message.follow_up_status_on_way', [], 'my'),
            'completed' => __('message.follow_up_status_delivered', [], 'my'),
            'pending' => __('message.follow_up_status_pending', [], 'my'),
            'pickup_error' => $this->t('dispatch_audit_title_pickup_error'),
            'cancelled' => $this->t('dispatch_audit_title_cancelled'),
            default => $this->t('dispatch_audit_log'),
        };
    }

    protected function iconFor(string $type): string
    {
        return match ($type) {
            self::TYPE_ORDER_CREATED, 'create', 'draft' => 'fa-solid fa-file-pen',
            self::TYPE_PICKUP_RIDER_ASSIGNED, 'courier_assigned' => 'fa-solid fa-user-check',
            self::TYPE_ADMIN_ITEM_INFO, self::TYPE_OS_ITEM_INFO => 'fa-solid fa-pen-to-square',
            self::TYPE_DELI_AMOUNT => 'fa-solid fa-coins',
            self::TYPE_RIDER_ITEM_INFO => 'fa-solid fa-motorcycle',
            self::TYPE_PICKUP_COMPLETED, 'courier_picked_up' => 'fa-solid fa-box-open',
            self::TYPE_DELIVERY_RIDER_ASSIGNED => 'fa-solid fa-truck',
            self::TYPE_DELIVERY_ITEM_STATUS => 'fa-solid fa-arrows-rotate',
            self::TYPE_RESTORED => 'fa-solid fa-rotate-left',
            self::TYPE_USER_CHOICE => 'fa-solid fa-hand-pointer',
            self::TYPE_ITEM_CREATED => 'fa-solid fa-plus',
            self::TYPE_ITEM_DELETED => 'fa-solid fa-trash',
            self::TYPE_PHOTO_UPLOADED => 'fa-solid fa-camera',
            self::TYPE_PRE_PICKUP_MOVED => 'fa-solid fa-arrow-right-arrow-left',
            self::TYPE_MOVED_TO_ASSIGN_100 => 'fa-solid fa-list-check',
            'courier_departed' => 'fa-solid fa-road',
            'completed' => 'fa-solid fa-circle-check',
            'pending' => 'fa-solid fa-hourglass-half',
            'cancelled', 'pickup_error' => 'fa-solid fa-ban',
            default => 'fa-solid fa-clock-rotate-left',
        };
    }

    protected function toneFor(string $type): string
    {
        return match ($type) {
            self::TYPE_ORDER_CREATED, 'create', 'draft' => 'info',
            self::TYPE_PICKUP_RIDER_ASSIGNED, 'courier_assigned', self::TYPE_DELIVERY_RIDER_ASSIGNED => 'assign',
            self::TYPE_ADMIN_ITEM_INFO, self::TYPE_RIDER_ITEM_INFO, self::TYPE_OS_ITEM_INFO, self::TYPE_ITEM_CREATED, self::TYPE_DELI_AMOUNT => 'edit',
            self::TYPE_PICKUP_COMPLETED, 'courier_picked_up', 'completed', self::TYPE_PHOTO_UPLOADED => 'success',
            self::TYPE_DELIVERY_ITEM_STATUS, 'courier_departed', 'pending' => 'info',
            self::TYPE_RESTORED, self::TYPE_PRE_PICKUP_MOVED, self::TYPE_MOVED_TO_ASSIGN_100 => 'restore',
            self::TYPE_USER_CHOICE => 'info',
            self::TYPE_ITEM_DELETED => 'danger',
            'courier_departed' => 'transit',
            'cancelled', 'pickup_error' => 'danger',
            default => 'neutral',
        };
    }

    protected function t(string $key, array $replace = []): string
    {
        return __('message.' . $key, $replace, 'my');
    }

    protected function formatTime($value): string
    {
        return Carbon::parse($value)->timezone('Asia/Yangon')->format('d-m-Y H:i');
    }
}
