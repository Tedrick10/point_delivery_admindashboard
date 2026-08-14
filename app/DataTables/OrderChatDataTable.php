<?php

namespace App\DataTables;

use App\Models\Order;
use App\Traits\DataTableTrait;
use Yajra\DataTables\Services\DataTable;

class OrderChatDataTable extends DataTable
{
    use DataTableTrait;

    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->editColumn('client_id', fn($row) => optional($row->client)->name ?? '-')
            ->editColumn('delivery_man_id', fn($row) => optional($row->delivery_man)->name ?? '-')
            ->editColumn('status', fn($row) => orderStatus($row->status))
            ->addColumn('chat_count', function ($row) {
                return \App\Models\OrderChatMessage::where('order_id', $row->id)->count();
            })
            ->addColumn('action', function ($row) {
                return '<a href="' . route('order-chat.show', $row->id) . '" class="btn btn-sm btn-primary"><i class="fa fa-comments"></i> ' . __('message.view_chat') . '</a>';
            })
            ->addIndexColumn()
            ->rawColumns(['action', 'status']);
    }

    public function query(Order $model)
    {
        return $model->newQuery()
            ->whereHas('orderChatMessages')
            ->with(['client', 'delivery_man'])
            ->orderBy('id', 'desc');
    }

    protected function getColumns()
    {
        return [
            ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false],
            ['data' => 'id', 'title' => __('message.order_id')],
            ['data' => 'client_id', 'title' => __('message.client')],
            ['data' => 'delivery_man_id', 'title' => __('message.delivery_man')],
            ['data' => 'status', 'title' => __('message.status')],
            ['data' => 'chat_count', 'title' => __('message.messages')],
            ['data' => 'action', 'title' => __('message.action'), 'orderable' => false, 'searchable' => false],
        ];
    }
}
