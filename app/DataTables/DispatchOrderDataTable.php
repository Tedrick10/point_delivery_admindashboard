<?php

namespace App\DataTables;

use App\Models\Order;
use App\Models\User;
use App\Services\DispatchOrderWorkflowService;
use App\Services\PhotoOrderDispatchService;
use App\Services\TextOrderDispatchService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Yajra\DataTables\Html\Column;

class DispatchOrderDataTable extends OrderDataTable
{
    protected ?Collection $pickupRiders = null;

    protected function pickupRiders(): Collection
    {
        if ($this->pickupRiders === null) {
            $this->pickupRiders = User::select('id', 'name')
                ->where('user_type', 'delivery_man')
                ->where('status', 1)
                ->orderBy('name')
                ->get();
        }

        return $this->pickupRiders;
    }

    public function dataTable($query)
    {
        $workflow = app(DispatchOrderWorkflowService::class);
        $pickupRiders = $this->pickupRiders();

        return datatables()
            ->eloquent($query)
            ->addIndexColumn()
            ->addColumn('received_date', function ($row) {
                $date = $row->pickup_datetime ?? $row->created_at;

                return formatDispatchYangonDate($date);
            })
            ->addColumn('order_type', function ($row) {
                $type = resolveDispatchOrderType($row);

                return '<span class="pds-order-type-badge ' . e($type['class']) . '">' . e($type['label']) . '</span>';
            })
            ->editColumn('admin_status', function ($row) {
                $this->ensureDispatchItemsSynced($row);

                return view('order.dispatch-admin-status', ['order' => $row])->render();
            })
            ->editColumn('rider_status', function ($row) {
                return view('order.dispatch-rider-status', ['order' => $row])->render();
            })
            ->editColumn('pickup_rider', function ($row) use ($pickupRiders, $workflow) {
                if (!auth()->user()->can('order-edit')) {
                    return optional($row->delivery_man)->name ?? '-';
                }

                // Pre Pick Up: rider cannot be assigned until the received day.
                if (request('dispatch_status') === 'pre_order' || $workflow->isPrePickUpOrder($row)) {
                    return optional($row->delivery_man)->name ?? '-';
                }

                if (($row->status ?? '') === 'pickup_error') {
                    return optional($row->delivery_man)->name ?? '-';
                }

                return view('order.dispatch-pickup-rider-select', [
                    'order' => $row,
                    'pickupRiders' => $pickupRiders,
                ])->render();
            })
            ->editColumn('os_name', function ($row) {
                return stringLong(resolveDispatchOsName($row), 'title', 40);
            })
            ->editColumn('phone', function ($row) {
                return resolveDispatchOsPhone($row);
            })
            ->editColumn('address_code', function ($row) {
                $address = resolveDispatchOsAddress($row);
                return '<span data-toggle="tooltip" title="' . e($address) . '">' . stringLong($address, 'title', 18) . '</span>';
            })
            ->editColumn('order_count', function ($row) {
                return 1;
            })
            ->editColumn('item_count', function ($row) use ($workflow) {
                $this->ensureDispatchItemsSynced($row);
                $count = (int) ($row->dispatch_items_count ?? $row->dispatchItems()->where('status', 'collected')->count());

                $progress = $workflow->adminProgress($row);
                if ($progress['total'] > 0 && !$progress['is_complete']) {
                    $label = $progress['updated'] . '/' . $progress['total'];
                } elseif ((int) $count === 0 && (int) ($row->total_parcel ?? 0) > 0) {
                    // Text orders awaiting Item Count entries.
                    $label = '0/' . (int) $row->total_parcel;
                } else {
                    $label = (string) $count;
                }

                if (!auth()->user()->can('order-list')) {
                    return '<span class="pds-dispatch-item-count">' . e($label) . '</span>';
                }

                $url = route('order.dispatch.items', $row->id);

                return '<a href="' . e($url) . '" class="pds-dispatch-item-count is-link" title="' . e(__('message.order_detail_list')) . '">' . e($label) . '</a>';
            })
            ->editColumn('deli_amount', function ($row) {
                // Show sum of item deli amounts — not order pricing total_amount.
                $amount = (float) $row->dispatchItems()
                    ->where('status', 'collected')
                    ->sum('deli_amount');

                return number_format($amount);
            })
            ->editColumn('remark', function ($row) {
                $workflow = app(DispatchOrderWorkflowService::class);
                if ((($row->status ?? '') === 'pickup_error' || $workflow->isPickupErrorCancelled($row))
                    && ! empty(trim((string) ($row->reason ?? '')))) {
                    $error = trim((string) $row->reason);

                    return '<span class="text-danger" data-toggle="tooltip" title="' . e($error) . '">'
                        . e(stringLong($error, 'title', 28) ?: $error)
                        . '</span>';
                }

                return stringLong($row->description ?? '', 'title', 20) ?: '-';
            })
            ->editColumn('order_date', function ($row) {
                return $row->created_at ? Carbon::parse($row->created_at)->format('d-m-Y H:i:s') : '-';
            })
            ->editColumn('id', function ($row) {
                $url = route('order.dispatch.edit', $row->id);

                return '<a href="' . $url . '">' . $row->id . '</a>';
            })
            ->addColumn('action', function ($order) {
                return view('order.dispatch-action', compact('order'))->render();
            })
            ->filter(function ($query) {
                $search = request('search_term');
                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('id', 'like', '%' . $search . '%')
                            ->orWhere('description', 'like', '%' . $search . '%')
                            ->orWhere('reason', 'like', '%' . $search . '%')
                            ->orWhere('pickup_point', 'like', '%' . $search . '%')
                            ->orWhereHas('client', function ($client) use ($search) {
                                $client->where('name', 'like', '%' . $search . '%')
                                    ->orWhere('contact_number', 'like', '%' . $search . '%');
                            })
                            ->orWhereHas('delivery_man', function ($rider) use ($search) {
                                $rider->where('name', 'like', '%' . $search . '%');
                            });
                    });
                }

                app(DispatchOrderWorkflowService::class)->applyDispatchStatusFilter(
                    $query,
                    $this->resolveOrderListDispatchStatus(request('dispatch_status'))
                );
            })
            ->order(function ($query) {
                if (request()->has('order')) {
                    $order = request()->order[0] ?? [];
                    $columnIndex = (int) ($order['column'] ?? 1);
                    $direction = strtolower((string) ($order['dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

                    $data = (string) (request()->columns[$columnIndex]['data'] ?? 'id');
                    $name = (string) (request()->columns[$columnIndex]['name'] ?? $data);

                    // Map display aliases → real orders columns (never order by virtual fields).
                    $columnMap = [
                        'received_date' => 'pickup_datetime',
                        'order_date' => 'created_at',
                        'order_count' => 'total_parcel',
                        'deli_amount' => 'total_amount',
                        'remark' => 'description',
                        'pickup_rider' => 'delivery_man_id',
                        'DT_RowIndex' => 'id',
                        'order_type' => 'id',
                        'admin_status' => 'id',
                        'rider_status' => 'id',
                        'os_name' => 'id',
                        'phone' => 'id',
                        'address_code' => 'id',
                        'item_count' => 'id',
                        'action' => 'id',
                    ];

                    $columnName = $columnMap[$data] ?? $columnMap[$name] ?? $name;
                    $allowed = [
                        'id',
                        'pickup_datetime',
                        'created_at',
                        'total_parcel',
                        'total_amount',
                        'description',
                        'delivery_man_id',
                        'status',
                    ];
                    if (! in_array($columnName, $allowed, true)) {
                        $columnName = 'id';
                    }

                    $query->orderBy($columnName, $direction);

                    return;
                }

                $query->orderByDesc('id');
            })
            ->rawColumns(['action', 'admin_status', 'rider_status', 'pickup_rider', 'address_code', 'item_count', 'id', 'order_type', 'remark']);
    }

    protected function getColumns()
    {
        $columns = [
            ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'title' => __('message.no'), 'orderable' => false, 'searchable' => false, 'width' => 40],
            ['data' => 'id', 'name' => 'id', 'title' => __('message.order_id')],
            ['data' => 'order_type', 'name' => 'order_type', 'title' => __('message.order_type'), 'orderable' => false, 'searchable' => false, 'width' => 120],
            ['data' => 'received_date', 'name' => 'pickup_datetime', 'title' => __('message.received_date')],
        ];

        // Pick Up Cancelled: show Order Status (user choice) instead of admin/rider/pickup rider.
        if (request('dispatch_status') === 'rider_pick_up_cancelled') {
            $columns = array_merge($columns, [
                ['data' => 'rider_status', 'name' => 'rider_status', 'title' => __('message.order_status'), 'orderable' => false, 'searchable' => false],
            ]);
        } else {
            $columns = array_merge($columns, [
                ['data' => 'admin_status', 'name' => 'admin_status', 'title' => __('message.admin_status'), 'orderable' => false, 'searchable' => false],
                ['data' => 'rider_status', 'name' => 'rider_status', 'title' => __('message.rider_status'), 'orderable' => false, 'searchable' => false],
                ['data' => 'pickup_rider', 'name' => 'delivery_man_id', 'title' => __('message.pickup_rider'), 'orderable' => false, 'width' => 190],
            ]);
        }

        $dispatchStatus = request('dispatch_status');
        $isDedicated = in_array($dispatchStatus, ['rider_pick_up_error', 'rider_pick_up_cancelled', 'pre_order'], true);
        $isPickUpTab = ! $isDedicated
            && ($dispatchStatus === null || $dispatchStatus === '' || $dispatchStatus === 'rider_pick_up_unassigned');

        $tail = [
            ['data' => 'os_name', 'name' => 'os_name', 'title' => __('message.os_name'), 'orderable' => false],
            ['data' => 'phone', 'name' => 'phone', 'title' => __('message.phone'), 'orderable' => false],
            ['data' => 'address_code', 'name' => 'address_code', 'title' => __('message.address'), 'orderable' => false],
            ['data' => 'order_count', 'name' => 'total_parcel', 'title' => __('message.order_count')],
        ];

        if (! $isPickUpTab) {
            $tail[] = ['data' => 'item_count', 'name' => 'item_count', 'title' => __('message.item_count'), 'orderable' => false];
        }

        return array_merge($columns, $tail, [
            ['data' => 'deli_amount', 'name' => 'total_amount', 'title' => __('message.deli_amount')],
            ['data' => 'remark', 'name' => 'description', 'title' => __('message.remark')],
            ['data' => 'order_date', 'name' => 'created_at', 'title' => __('message.date')],
            Column::computed('action')
                ->title(__('message.action'))
                ->exportable(false)
                ->printable(false)
                ->width(80)
                ->addClass('text-center'),
        ]);
    }

    public function getBuilderParameters(): array
    {
        $params = parent::getBuilderParameters();
        $params['dom'] = '<"pds-dispatch-dt-top" f>rt<"d-flex" <"flex-grow-1" l><"p-2" i><"mt-4" p>><"clear">';
        $params['searching'] = false;
        $params['order'] = [[1, 'desc']];

        return $params;
    }

    public function query(Order $model)
    {
        $workflow = app(DispatchOrderWorkflowService::class);
        $dispatchStatus = request('dispatch_status');
        $isDedicated = $workflow->isDedicatedPickupList($dispatchStatus);
        $isPreOrder = $dispatchStatus === 'pre_order';
        $yangonToday = \Carbon\Carbon::now('Asia/Yangon');

        // Dedicated pickup error/cancelled: last 30 days.
        // Pre Order: today → +14 days (future received dates).
        // Main Order List: Yangon "today".
        if ($isPreOrder) {
            $defaultFrom = $yangonToday->format('d-m-Y');
            $defaultTo = $yangonToday->copy()->addDays(14)->format('d-m-Y');
        } elseif ($isDedicated) {
            $defaultFrom = $yangonToday->copy()->subDays(30)->format('d-m-Y');
            $defaultTo = $yangonToday->format('d-m-Y');
        } else {
            $defaultFrom = $yangonToday->format('d-m-Y');
            $defaultTo = $yangonToday->format('d-m-Y');
        }

        $rawFrom = request('from_date') ?: $defaultFrom;
        $rawTo = request('to_date') ?: $defaultTo;

        try {
            $dateBounds = parseDispatchFilterDateBounds($rawFrom, $rawTo);
            request()->merge([
                'from_date' => Carbon::createFromFormat('d-m-Y', $rawFrom, 'Asia/Yangon')->format('d-m-Y'),
                'to_date' => Carbon::createFromFormat('d-m-Y', $rawTo, 'Asia/Yangon')->format('d-m-Y'),
            ]);
        } catch (\Exception $e) {
            $dateBounds = null;
        }

        $savedStatus = request('status');
        request()->offsetUnset('status');

        // Filter dispatch list by received date (pickup_datetime), not created_at.
        $savedFromDate = request('from_date');
        $savedToDate = request('to_date');
        request()->offsetUnset('from_date');
        request()->offsetUnset('to_date');

        $query = parent::query($model)
            ->with(['delivery_man', 'client'])
            ->withCount(['dispatchItems as dispatch_items_count' => function ($query) {
                $query->where('status', 'collected');
            }]);

        request()->merge([
            'from_date' => $savedFromDate,
            'to_date' => $savedToDate,
            'status' => $savedStatus,
        ]);

        if ($isDedicated) {
            $workflow->applyDedicatedPickupListQuery($query, (string) $dispatchStatus);
        } else {
            // Pull back Admin-Done-only items that landed in Assign 100 too early.
            $workflow->reclaimPrematureAssign100Items();
            $workflow->applyOrderListQuery($query);
        }

        if ($dateBounds) {
            if ($isPreOrder) {
                $query->whereBetween('pickup_datetime', [
                    $dateBounds['pickup_from'],
                    $dateBounds['pickup_to'],
                ]);
            } else {
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
        }

        return $query->whereNull($model->getQualifiedDeletedAtColumn())
            ->orderByDesc('id');
    }

    /**
     * Create missing dispatch items before Admin Status / Item Count are rendered.
     * Photo/Text/Shop/Gate orders can appear on the list with zero items until synced.
     */
    protected function ensureDispatchItemsSynced(Order $row): void
    {
        if ((int) ($row->dispatch_items_count ?? 0) > 0) {
            return;
        }

        // Re-check live count in case another column already synced this row.
        $liveCount = $row->dispatchItems()->where('status', 'collected')->count();
        if ($liveCount > 0) {
            $row->setAttribute('dispatch_items_count', $liveCount);

            return;
        }

        $textService = app(TextOrderDispatchService::class);

        if ((int) ($row->is_photo_order ?? 0) === 1) {
            app(PhotoOrderDispatchService::class)->sync($row);
        } elseif ((int) ($row->is_text_order ?? 0) === 1 && ! $textService->hasAdvancedParcelItems($row)) {
            $textService->ensureTextOrderItem($row);
        } elseif (((int) ($row->is_shop_order ?? 0) === 1 || (int) ($row->is_gate_order ?? 0) === 1)
            && ! $textService->hasAdvancedParcelItems($row)) {
            $textService->sync($row);
        } else {
            return;
        }

        $row->setAttribute(
            'dispatch_items_count',
            $row->dispatchItems()->where('status', 'collected')->count()
        );
    }

    /**
     * Main Order List tabs: default to Pick Up (unassigned). Dedicated lists keep their status.
     */
    private function resolveOrderListDispatchStatus(?string $status): ?string
    {
        $workflow = app(DispatchOrderWorkflowService::class);
        if ($workflow->isDedicatedPickupList($status)) {
            return $status;
        }

        $tabs = [
            'rider_pick_up_unassigned',
            'rider_pick_up_assigned',
            'rider_pick_up_done',
            'admin_completed',
        ];

        if ($status && in_array($status, $tabs, true)) {
            return $status;
        }

        return 'rider_pick_up_unassigned';
    }
}
