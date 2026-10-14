<?php

namespace App\DataTables;

use App\Models\DispatchOrderItem;
use App\Traits\DataTableTrait;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class DispatchOrderItemDataTable extends DataTable
{
    use DataTableTrait;

    protected $orderId;

    protected $toBranchId;

    public function with(array|string $key, mixed $value = null): static
    {
        if (is_array($key)) {
            if (isset($key['order_id'])) {
                $this->orderId = $key['order_id'];
            }
            if (array_key_exists('to_branch_id', $key)) {
                $this->toBranchId = (int) $key['to_branch_id'];
            }
        } elseif ($key === 'order_id') {
            $this->orderId = $value;
        } elseif ($key === 'to_branch_id') {
            $this->toBranchId = (int) $value;
        }

        return parent::with($key, $value);
    }

    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addIndexColumn()
            ->addColumn('checkbox', function ($row) {
                $alreadyGiven = $row->relationLoaded('kyoShinItem')
                    ? $row->kyoShinItem !== null
                    : $row->kyoShinItem()->exists();
                $disabled = $alreadyGiven ? ' disabled' : '';
                $title = $alreadyGiven ? ' title="'.e(__('message.kyo_shin_already_given')).'"' : '';

                return '<input type="checkbox" class="pds-dispatch-item-check" value="'.$row->id.'"'
                    .' data-item-value="'.e((float) ($row->item_value ?? 0)).'"'
                    .' data-item-code="'.e((string) ($row->code ?? '')).'"'
                    .$disabled.$title.'>';
            })
            ->editColumn('received_date', function ($row) {
                return formatDispatchYangonDate($row->received_date);
            })
            ->editColumn('updated_at', function ($row) {
                return formatDispatchYangonDate($row->updated_at);
            })
            ->editColumn('code', function ($row) {
                $code = e($row->code ?? '-');
                if ($row->kyoShinItem) {
                    return $code . ' <span class="pds-kyo-shin-row-badge">' . e(__('message.kyo_shin_title')) . '</span>';
                }

                return $code;
            })
            ->editColumn('status', function ($row) {
                $status = strtolower((string) ($row->status ?? 'collected'));
                $label = strtoupper($status !== '' ? $status : 'collected');
                $tone = match ($status) {
                    'collected', 'completed', 'delivered', 'finished' => 'success',
                    'cancelled', 'cancel', 'error', 'failed' => 'danger',
                    'assigned', 'picked', 'on_way', 'pending' => 'info',
                    default => 'neutral',
                };

                return '<span class="pds-dispatch-item-status pds-dispatch-item-status--'.$tone.'">' . e($label) . '</span>';
            })
            ->editColumn('photo_id', function ($row) {
                $order = $row->relationLoaded('order') ? $row->order : $row->order()->first();
                $canEdit = auth()->user()->can('order-edit')
                    && $order
                    && app(\App\Services\DispatchOrderWorkflowService::class)->canAdminEditDispatchItemInfo($order, $row);
                $editUrl = route('order.dispatch.item.edit', [$row->order_id, $row->id]);
                $editTitle = e(__('message.add_or_update_item'));

                // Inline SVG so the pen never depends on Font Awesome / table icon CSS.
                $penSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="12" height="12" aria-hidden="true" focusable="false">'
                    . '<path fill="currentColor" d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zm17.71-10.04a1.003 1.003 0 0 0 0-1.42l-2.5-2.5a1.003 1.003 0 0 0-1.42 0l-1.83 1.83 3.75 3.75 1.99-1.66z"/>'
                    . '</svg>';

                $editBtn = '';
                if ($canEdit) {
                    $editBtn = '<a href="' . e($editUrl) . '"'
                        . ' class="pds-dispatch-photo-edit-btn loadRemoteModel"'
                        . ' title="' . $editTitle . '"'
                        . ' aria-label="' . $editTitle . '">'
                        . $penSvg
                        . '</a>';
                }

                $media = ($row->photo_id && $row->photoMedia) ? $row->photoMedia : null;
                $url = null;
                if ($media) {
                    $url = mediaPublicUrl($media) ?: null;
                    if (($url === null || $url === '') && method_exists($media, 'getUrl')) {
                        try {
                            $url = (string) $media->getUrl();
                        } catch (\Throwable $e) {
                            $url = null;
                        }
                    }
                    if (($url === null || $url === '') && function_exists('mediaAbsoluteUrl')) {
                        $url = mediaAbsoluteUrl($media) ?: null;
                    }
                }

                $name = e(__('message.photo'));
                $imgHtml = '';
                if ($url !== null && $url !== '') {
                    $cacheV = optional($media->updated_at)->timestamp ?: time();
                    $url = $url . (str_contains($url, '?') ? '&' : '?') . 'v=' . $cacheV;
                    $imgHtml = '<img src="' . e($url) . '" alt="" class="pds-dispatch-photo-thumb" loading="lazy"'
                        . ' onerror="this.onerror=null;var w=this.closest(\'.pds-dispatch-photo-thumb-wrap\');if(w){w.classList.add(\'is-broken\');}this.remove();">';
                }

                $wrapClass = 'pds-dispatch-photo-thumb-wrap'
                    . ($imgHtml === '' ? ' is-missing' : '')
                    . ($editBtn === '' ? ' is-readonly' : '');

                return '<div class="' . $wrapClass . '">'
                    . $editBtn
                    . '<button type="button" class="pds-dispatch-photo-thumb-btn"'
                    . ($imgHtml !== '' ? ' data-photo-url="' . e($url) . '" data-photo-name="' . $name . '"' : ' disabled')
                    . ' title="' . $name . '">'
                    . $imgHtml
                    . '</button>'
                    . '</div>';
            })
            ->editColumn('to_branch_id', function ($row) {
                return optional($row->toBranch)->name ?? '-';
            })
            ->editColumn('customer_name', function ($row) {
                return e($row->customer_name ?? '-');
            })
            ->editColumn('customer_phone', function ($row) {
                $raw = $row->getAttributes()['customer_phone'] ?? $row->customer_phone ?? '';
                $phone = normalizeContactNumber((string) $raw);

                return e($phone !== '' ? $phone : '-');
            })
            ->editColumn('customer_address', function ($row) {
                $address = $row->customer_address ?? '-';
                return '<span title="' . e($address) . '">' . stringLong($address, 'title', 24) . '</span>';
            })
            ->editColumn('township', function ($row) {
                $label = DispatchOrderItem::deliveryCityLabel($row->delivery_city);
                if ($label === '-' && $row->township) {
                    $label = $row->township;
                }

                return e($label);
            })
            ->editColumn('item_name', function ($row) {
                return stringLong($row->item_name ?? '', 'title', 20) ?: '-';
            })
            ->editColumn('remark', function ($row) {
                $remark = trim((string) ($row->remark ?? ''));
                $normalized = strtolower(preg_replace('/\s+/', '_', $remark) ?? '');
                // Empty / system "rider_added" (Rider Done) → green Done badge.
                if ($remark === '' || $remark === '-' || $normalized === 'rider_added' || $normalized === 'rider_done') {
                    return '<span class="pds-dispatch-item-remark pds-dispatch-item-remark--done">'
                        . e(__('message.done'))
                        . '</span>';
                }

                return e(stringLong($remark, 'title', 16) ?: $remark);
            })
            ->editColumn('weight', function ($row) {
                return formatDispatchItemSize($row->weight);
            })
            ->editColumn('advance_paid', function ($row) {
                return $this->formatDispatchAmount($row->advance_paid, true);
            })
            ->editColumn('item_value', function ($row) {
                return $this->formatDispatchAmount($row->item_value);
            })
            ->editColumn('deli_amount', function ($row) {
                $raw = (float) ($row->deli_amount ?? 0);

                return '<span class="pds-dt-amount" data-amount="'.$raw.'">'.formatDispatchDeliAmountHtml($row).'</span>';
            })
            ->editColumn('cust_get', function ($row) {
                return $this->formatDispatchAmount($row->cust_get);
            })
            ->editColumn('os_to_pay', function ($row) {
                return $this->formatDispatchAmount($row->displayOsToPay());
            })
            ->addColumn('action', function ($item) {
                return view('order.dispatch-item-action', [
                    'item' => $item,
                    'hideGate' => true,
                ])->render();
            })
            ->rawColumns([
                'checkbox', 'code', 'status', 'customer_address', 'photo_id', 'remark',
                'advance_paid', 'item_value', 'deli_amount', 'cust_get', 'os_to_pay', 'action',
            ])
            ->with('pds_totals', $this->totalsForQuery($query));
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return array<string, float>
     */
    protected function totalsForQuery($query): array
    {
        $items = (clone $query)->get();

        return [
            'advance_paid' => round((float) $items->sum('advance_paid'), 2),
            'item_value' => round((float) $items->sum('item_value'), 2),
            'deli_amount' => round((float) $items->sum('deli_amount'), 2),
            'cust_get' => round((float) $items->sum('cust_get'), 2),
            'os_to_pay' => round((float) $items->sum(fn ($item) => $item->displayOsToPay()), 2),
        ];
    }

    public function query(DispatchOrderItem $model)
    {
        $orderId = $this->orderId ?? request()->route('id');

        $statuses = app(\App\Services\DispatchOrderWorkflowService::class)->clientVisibleItemStatuses();

        $toBranchId = (int) ($this->toBranchId ?? request()->input('to_branch_id', 0));

        return $model->newQuery()
            ->where('order_id', $orderId)
            ->whereIn('status', $statuses)
            ->when($toBranchId > 0, fn ($q) => $q->where('to_branch_id', $toBranchId))
            ->with(['toBranch', 'photoMedia', 'order', 'kyoShinItem']);
    }

    protected function getColumns()
    {
        return [
            [
                'data' => 'checkbox',
                'name' => 'checkbox',
                'title' => '<input type="checkbox" id="dispatchItemsSelectAll" class="pds-dispatch-item-check-all" title="' . e(__('message.select_all')) . '">',
                'orderable' => false,
                'searchable' => false,
                'width' => 36,
                'className' => 'text-center',
            ],
            ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'title' => __('message.no'), 'orderable' => false, 'searchable' => false, 'width' => 40],
            ['data' => 'photo_id', 'name' => 'photo_id', 'title' => __('message.photo_order_images'), 'orderable' => false, 'searchable' => false, 'width' => 70],
            ['data' => 'id', 'name' => 'id', 'title' => __('message.item_id'), 'orderable' => false],
            ['data' => 'received_date', 'name' => 'received_date', 'title' => __('message.received_date'), 'orderable' => false],
            ['data' => 'updated_at', 'name' => 'updated_at', 'title' => __('message.modified_date'), 'orderable' => false],
            ['data' => 'code', 'name' => 'code', 'title' => __('message.code'), 'orderable' => false],
            ['data' => 'status', 'name' => 'status', 'title' => __('message.status'), 'orderable' => false],
            ['data' => 'to_branch_id', 'name' => 'to_branch_id', 'title' => __('message.to'), 'orderable' => false],
            ['data' => 'customer_name', 'name' => 'customer_name', 'title' => __('message.name'), 'orderable' => false],
            ['data' => 'customer_phone', 'name' => 'customer_phone', 'title' => __('message.phone'), 'orderable' => false],
            ['data' => 'customer_address', 'name' => 'customer_address', 'title' => __('message.address'), 'orderable' => false],
            ['data' => 'township', 'name' => 'township', 'title' => __('message.township'), 'orderable' => false],
            ['data' => 'item_name', 'name' => 'item_name', 'title' => __('message.item_name'), 'orderable' => false],
            ['data' => 'remark', 'name' => 'remark', 'title' => __('message.remark_label'), 'orderable' => false],
            // No column footers — totals live in #dispatchItemsDock. DT tfoot was the empty white bar.
            ['data' => 'weight', 'name' => 'weight', 'title' => __('message.size'), 'orderable' => false],
            ['data' => 'advance_paid', 'name' => 'advance_paid', 'title' => __('message.advance_paid'), 'orderable' => false, 'className' => 'text-right'],
            ['data' => 'item_value', 'name' => 'item_value', 'title' => __('message.item_value'), 'orderable' => false, 'className' => 'text-right'],
            ['data' => 'deli_amount', 'name' => 'deli_amount', 'title' => __('message.deli_amount'), 'orderable' => false, 'width' => 140, 'className' => 'text-right'],
            ['data' => 'cust_get', 'name' => 'cust_get', 'title' => __('message.cust_get'), 'orderable' => false, 'className' => 'text-right'],
            ['data' => 'os_to_pay', 'name' => 'os_to_pay', 'title' => __('message.os_to_pay'), 'orderable' => false, 'className' => 'text-right'],
            Column::computed('action')
                ->title(__('message.action'))
                ->exportable(false)
                ->printable(false)
                ->width(90)
                ->addClass('text-center'),
        ];
    }

    public function getBuilderParameters(): array
    {
        $params = $this->pdsDefaultBuilderParameters();
        // sDom overwrites dom in DataTables compat map — must clear both or empty chrome + slab remain.
        $params['dom'] = 't';
        $params['sDom'] = 't';
        $params['searching'] = false;
        $params['paging'] = false;
        $params['ordering'] = false;
        $params['order'] = [];
        // No DT scrollX/scrollY — those left an empty white slab under the last row.
        // .pds-dispatch-items-table-shell owns overflow; thead is CSS-sticky.
        $params['scrollX'] = '';
        $params['scrollY'] = '';
        $params['scrollCollapse'] = false;
        $params['autoWidth'] = false;
        $params['info'] = false;
        $params['lengthChange'] = false;
        $params['buttons'] = [];
        $params['language'] = array_merge($params['language'] ?? [], [
            'emptyTable' => __('message.no_record_found'),
            'zeroRecords' => __('message.no_record_found'),
        ]);
        $params['drawCallback'] = 'function () {
            var api = this.api();
            function realign() {
                try {
                    var table = api.table().node();
                    if (table) {
                        $(table).find("tfoot").remove();
                        var $wrap = $(table).closest(".dataTables_wrapper");
                        $wrap.find(".dataTables_scrollFoot, tfoot").remove();
                    }
                } catch (eFoot) {}
                if (typeof window.pdsFillDispatchItemsTotals === "function") {
                    try { window.pdsFillDispatchItemsTotals(api); } catch (e2) {}
                }
                if (typeof window.pdsFitDispatchItemsScroll === "function") {
                    try { window.pdsFitDispatchItemsScroll(api); } catch (eFit) {}
                }
            }
            realign();
            setTimeout(realign, 100);
        }';

        return $params;
    }

    private function formatDispatchAmount($value, bool $blankWhenZero = false): string
    {
        $amount = (float) $value;

        if ($blankWhenZero && $amount == 0.0) {
            return '';
        }

        $text = ($blankWhenZero && $amount == 0.0) ? '' : number_format($amount);

        return '<span class="pds-dt-amount" data-amount="'.$amount.'">'.$text.'</span>';
    }
}
