<?php

namespace App\DataTables;

use App\Models\Advertisement;
use App\Traits\DataTableTrait;
use Yajra\DataTables\Services\DataTable;

class AdvertisementDataTable extends DataTable
{
    use DataTableTrait;

    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->editColumn('placement', fn($row) => ucfirst(str_replace('_', ' ', $row->placement)))
            ->editColumn('target_app', fn($row) => ucfirst(str_replace('_', ' ', $row->target_app)))
            ->editColumn('approval_status', function ($row) {
                $badges = [
                    'pending' => 'warning',
                    'approved' => 'success',
                    'rejected' => 'danger',
                ];
                $color = $badges[$row->approval_status] ?? 'secondary';
                return '<span class="badge badge-' . $color . '">' . ucfirst($row->approval_status) . '</span>';
            })
            ->editColumn('status', function ($row) {
                return $row->status == 1
                    ? '<span class="badge badge-success">' . __('message.enable') . '</span>'
                    : '<span class="badge badge-danger">' . __('message.disable') . '</span>';
            })
            ->addColumn('action', function ($row) {
                $id = $row->id;
                return view('advertisement.action', compact('id', 'row'))->render();
            })
            ->addIndexColumn()
            ->rawColumns(['action', 'approval_status', 'status']);
    }

    public function query(Advertisement $model)
    {
        return $model->newQuery()->orderBy('id', 'desc');
    }

    protected function getColumns()
    {
        return [
            ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false],
            ['data' => 'title', 'title' => __('message.title')],
            ['data' => 'ad_type', 'title' => __('message.type')],
            ['data' => 'placement', 'title' => __('message.placement')],
            ['data' => 'target_app', 'title' => __('message.target_app')],
            ['data' => 'approval_status', 'title' => __('message.approval_status')],
            ['data' => 'status', 'title' => __('message.status')],
            ['data' => 'action', 'title' => __('message.action'), 'orderable' => false, 'searchable' => false],
        ];
    }
}
