<?php

namespace App\DataTables;

use App\Models\Branch;
use App\Traits\DataTableTrait;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class BranchDataTable extends DataTable
{
    use DataTableTrait;

    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class=" select-table-row-checked-values" id="datatable-row-'.$row->id.'" name="datatable_ids[]" value="'.$row->id.'" onclick="dataTableRowCheck('.$row->id.')">';
            })
            ->editColumn('status', function ($data) {
                $delete_at = null;
                $action_type = 'status';

                return view('branch.action', compact('data', 'delete_at', 'action_type'))->render();
            })
            ->editColumn('created_at', function ($row) {
                return dateAgoFormate($row->created_at, true);
            })
            ->editColumn('name', function ($row) {
                return '<span class="text-capitalize">'.e($row->name).'</span>';
            })
            ->addColumn('action', function ($row) {
                $id = $row->id;
                $delete_at = $row->deleted_at;
                $action_type = 'action';

                return view('branch.action', compact('id', 'delete_at', 'action_type'))->render();
            })
            ->order(function ($query) {
                if (request()->has('order')) {
                    $order = request()->order[0];
                    $column_index = $order['column'];
                    $column_name = 'id';
                    $direction = 'desc';
                    if ($column_index != 0) {
                        $column_name = request()->columns[$column_index]['data'];
                        $direction = $order['dir'];
                    }
                    $query->orderBy($column_name, $direction);
                }
            })
            ->addIndexColumn()
            ->rawColumns(['checkbox', 'status', 'action', 'name']);
    }

    public function query(Branch $model)
    {
        return Branch::query()->withTrashed();
    }

    protected function getColumns()
    {
        return [
            Column::make('checkbox')
                ->searchable(false)
                ->title('<input type="checkbox" class ="select-all-table" name="select_all" id="select-all-table">')
                ->orderable(false)
                ->width(60),
            ['data' => 'id', 'name' => 'id', 'title' => __('message.id')],
            ['data' => 'name', 'name' => 'name', 'title' => __('message.branch_name')],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => __('message.created_at')],
            ['data' => 'status', 'name' => 'status', 'title' => __('message.status')],
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width(60)
                ->addClass('text-center hide-search'),
        ];
    }
}
