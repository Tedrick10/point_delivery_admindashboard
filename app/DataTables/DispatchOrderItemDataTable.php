<?php

namespace App\DataTables;

use App\Models\DispatchOrderItem;
use App\Traits\DataTableTrait;
use Carbon\Carbon;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class DispatchOrderItemDataTable extends DataTable
{
    use DataTableTrait;

    protected $orderId;

    public function with(array|string $key, mixed $value = null): static
    {
        if (is_array($key)) {
            if (isset($key['order_id'])) {
                $this->orderId = $key['order_id'];
            }
        } elseif ($key === 'order_id') {
            $this->orderId = $value;
        }

        return parent::with($key, $value);
    }

    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addIndexColumn()
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="pds-dispatch-item-check" value="' . $row->id . '">';
            })
            ->editColumn('received_date', function ($row) {
                return $row->received_date ? Carbon::parse($row->received_date)->format('d-m-Y') : '-';
            })
            ->editColumn('updated_at', function ($row) {
                return $row->updated_at ? Carbon::parse($row->updated_at)->format('d-m-Y') : '-';
            })
            ->editColumn('status', function ($row) {
                $label = strtoupper($row->status ?? 'collected');
                return '<span class="pds-dispatch-item-status">' . e($label) . '</span>';
            })
            ->editColumn('photo_id', function ($row) {
                $order = $row->relationLoaded('order') ? $row->order : $row->order()->first();
                $canEdit = auth()->user()->can('order-edit')
                    && $order
                    && app(\App\Services\DispatchOrderWorkflowService::class)->canAdminEditDispatchItemInfo($order, $row);
                $editUrl = route('order.dispatch.item.edit', [$row->order_id, $row->id]);
                $editTitle = e(__('message.add_or_update_item'));

                $editBtn = '';
                if ($canEdit) {
                    $editBtn = '<a href="' . e($editUrl) . '"'
                        . ' class="pds-dispatch-photo-edit-btn loadRemoteModel"'
                        . ' title="' . $editTitle . '"'
                        . ' aria-label="' . $editTitle . '">'
                        . '<i class="fas fa-pen" aria-hidden="true"></i>'
                        . '</a>';
                }

                if (!$row->photo_id || !$row->photoMedia) {
                    if ($editBtn === '') {
                        return '-';
                    }

                    return '<div class="pds-dispatch-photo-thumb-wrap is-edit-only">'
                        . $editBtn
                        . '</div>';
                }

                $url = mediaPublicUrl($row->photoMedia);
                $name = e($row->photoMedia->file_name ?? 'Photo');

                return '<div class="pds-dispatch-photo-thumb-wrap">'
                    . $editBtn
                    . '<button type="button" class="pds-dispatch-photo-thumb-btn" data-photo-url="' . e($url) . '" data-photo-name="' . $name . '" title="' . $name . '">'
                    . '<img src="' . e($url) . '" alt="' . $name . '" class="pds-dispatch-photo-thumb" loading="lazy">'
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
                $phone = normalizeContactNumber($row->customer_phone ?? '');

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
                return stringLong($row->remark ?? '', 'title', 16) ?: '-';
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
                $amount = $this->formatDispatchAmount($row->deli_amount);
                if ($amount === '') {
                    return '';
                }

                return formatDispatchDeliAmountHtml($row);
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
            ->rawColumns(['checkbox', 'status', 'customer_address', 'photo_id', 'deli_amount', 'action']);
    }

    public function query(DispatchOrderItem $model)
    {
        $orderId = $this->orderId ?? request()->route('id');

        return $model->newQuery()
            ->where('order_id', $orderId)
            ->where('status', 'collected')
            ->with(['toBranch', 'photoMedia', 'order']);
    }

    protected function getColumns()
    {
        return [
            ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'title' => __('message.no'), 'orderable' => false, 'searchable' => false, 'width' => 40],
            ['data' => 'photo_id', 'name' => 'photo_id', 'title' => __('message.photo_order_images'), 'orderable' => false, 'searchable' => false, 'width' => 70],
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '', 'orderable' => false, 'searchable' => false, 'width' => 30],
            ['data' => 'id', 'name' => 'id', 'title' => __('message.item_id')],
            ['data' => 'received_date', 'name' => 'received_date', 'title' => __('message.received_date')],
            ['data' => 'updated_at', 'name' => 'updated_at', 'title' => __('message.modified_date')],
            ['data' => 'code', 'name' => 'code', 'title' => __('message.code')],
            ['data' => 'status', 'name' => 'status', 'title' => __('message.status')],
            ['data' => 'to_branch_id', 'name' => 'to_branch_id', 'title' => __('message.to'), 'orderable' => false],
            ['data' => 'customer_name', 'name' => 'customer_name', 'title' => __('message.name')],
            ['data' => 'customer_phone', 'name' => 'customer_phone', 'title' => __('message.phone')],
            ['data' => 'customer_address', 'name' => 'customer_address', 'title' => __('message.address')],
            ['data' => 'township', 'name' => 'township', 'title' => __('message.township')],
            ['data' => 'item_name', 'name' => 'item_name', 'title' => __('message.item_name')],
            ['data' => 'remark', 'name' => 'remark', 'title' => __('message.remark_label')],
            ['data' => 'weight', 'name' => 'weight', 'title' => __('message.size')],
            ['data' => 'advance_paid', 'name' => 'advance_paid', 'title' => __('message.advance_paid')],
            ['data' => 'item_value', 'name' => 'item_value', 'title' => __('message.item_value')],
            ['data' => 'deli_amount', 'name' => 'deli_amount', 'title' => __('message.deli_amount'), 'width' => 140, 'className' => 'text-right'],
            ['data' => 'cust_get', 'name' => 'cust_get', 'title' => __('message.cust_get')],
            ['data' => 'os_to_pay', 'name' => 'os_to_pay', 'title' => __('message.os_to_pay')],
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
        $params = parent::getBuilderParameters();
        $params['dom'] = 'rt<"pds-dispatch-items-dt-footer" <"pds-dispatch-items-dt-info" i><"pds-dispatch-items-dt-length" l>><"clear">';
        $params['searching'] = false;
        $params['paging'] = true;
        $params['pageLength'] = 25;
        $params['scrollX'] = true;
        $params['buttons'] = [];
        $params['language'] = array_merge($params['language'] ?? [], [
            'emptyTable' => __('message.no_record_found'),
            'zeroRecords' => __('message.no_record_found'),
            'info' => __('message.datatable_info'),
            'infoEmpty' => '',
        ]);

        return $params;
    }

    private function formatDispatchAmount($value, bool $blankWhenZero = false): string
    {
        $amount = (float) $value;

        if ($blankWhenZero && $amount == 0.0) {
            return '';
        }

        return number_format($amount);
    }
}
