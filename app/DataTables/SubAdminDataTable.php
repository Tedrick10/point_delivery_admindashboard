<?php

namespace App\DataTables;

use App\Models\User;
use App\Models\EmployeeType;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use App\Traits\DataTableTrait;

class SubAdminDataTable extends DataTable
{
    use DataTableTrait;

    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->editColumn('checkbox', function ($row) {
                return '<input type="checkbox" class=" select-table-row-checked-values" id="datatable-row-' . $row->id . '" name="datatable_ids[]" value="' . $row->id . '" onclick="dataTableRowCheck(' . $row->id . ')">';
            })
            ->editColumn('created_at', function ($query) {
                return dateAgoFormate($query->created_at, true);
            })
            ->editColumn('contact_number', function ($query) {
                return auth()->user()->hasRole('admin') ? maskSensitiveInfo('contact_number', $query->contact_number) : maskSensitiveInfo('contact_number', $query->contact_number);
            })
            ->editColumn('email', function ($query) {
                return auth()->user()->hasRole('admin') ? maskSensitiveInfo('email', $query->email) : maskSensitiveInfo('email', $query->email);
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
            ->editColumn('user_type', function ($query) {
                return $query->user_type ? ucfirst(str_replace('_', ' ', $query->user_type)) : '-';
            })
            ->editColumn('otp_verify_at', function ($data) {
                if ($data->otp_verify_at !== null) {
                    return '<p class="text-capitalize badge bg-success text-center mt-3"> Verified</p>';
                }

                $action_type = 'verify';
                $deleted_at = null;

                return view('users.action', compact('data', 'action_type', 'deleted_at'))->render();
            })
            ->addColumn('rider_work_on', function ($row) {
                $on = (bool) $row->isRiderWorkOn();
                $canEdit = auth()->user()->can('subadmin-edit') || auth()->user()->can('users-edit');

                if (! $canEdit) {
                    return $on
                        ? '<span class="pds-dm-work-badge is-on">' . e(__('message.rider_work_on')) . '</span>'
                        : '<span class="pds-dm-work-badge is-off">' . e(__('message.rider_work_off')) . '</span>';
                }

                return '<label class="pds-dm-work-switch' . ($on ? ' is-on' : ' is-off') . '" title="' . e($on ? __('message.employee_work_on_hint') : __('message.employee_work_off_hint')) . '">'
                    . '<input type="checkbox" class="js-employee-work-toggle" data-id="' . (int) $row->id . '" ' . ($on ? 'checked' : '') . '>'
                    . '<span class="pds-dm-work-switch__track" aria-hidden="true"></span>'
                    . '<span class="pds-dm-work-switch__label">' . e($on ? __('message.rider_work_on') : __('message.rider_work_off')) . '</span>'
                    . '</label>';
            })
            ->addColumn('action', function ($row) {
                $id = $row->id;
                $action_type = 'action';
                $deleted_at = $row->deleted_at;

                return view('subadmin.action', compact('id', 'deleted_at', 'action_type'))->render();
            })
            ->addIndexColumn()
            ->rawColumns(['checkbox', 'action', 'rider_work_on', 'name', 'otp_verify_at', 'is_autoverified_mobile', 'is_autoverified_email']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param  \App\Models\User  $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(User $model)
    {
        app(\App\Services\RiderWorkStatusService::class)->resetExpiredOffRiders();

        $model = User::whereNotIn('user_type', ['admin', 'client', 'delivery_man', 'super_admin']);

        if (request('employee_type')) {
            $employeeType = EmployeeType::find(request('employee_type'));
            if ($employeeType) {
                $roleNames = $employeeType->roles()->pluck('name');
                $model->whereIn('user_type', $roleNames);
            }
        }

        if (request('role')) {
            $model->where('user_type', request('role'));
        }

        return $model->withTrashed();
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        $status = request('status');
        $columns = [
            Column::make('checkbox')
                ->searchable(false)
                ->orderable(false)
                ->title('<input type="checkbox" class ="select-all-table" name="select_all" id="select-all-table">')
                ->width(10),
            Column::make('DT_RowIndex')
                ->searchable(false)
                ->title(__('message.srno'))
                ->addClass('text-capitalize')
                ->orderable(false),
            ['data' => 'name', 'name' => 'name', 'title' => __('message.name'), 'class' => 'text-capitalize'],
            ['data' => 'user_type', 'name' => 'user_type', 'title' => __('message.role')],
            ['data' => 'email', 'name' => 'email', 'title' => __('message.email')],
            ['data' => 'contact_number', 'name' => 'contact_number', 'title' => __('message.contact_number')],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => __('message.created_at')],
        ];

        if ($status === 'pending') {
            $columns[] = Column::make('is_autoverified_email', 'is_autoverified_email')
                ->title(__('message.email') . ' ' . __('message.is_verify'))
                ->visible(true)
                ->orderable(false);

            $columns[] = Column::make('is_autoverified_mobile', 'is_autoverified_mobile')
                ->title(__('message.mobile') . ' ' . __('message.is_verify'))
                ->visible(true)
                ->orderable(false);
        } else {
            $columns[] = Column::make('rider_work_on', 'rider_work_on')
                ->title('On/Off')
                ->orderable(false)
                ->searchable(false)
                ->addClass('text-center');
        }

        $columns[] = Column::computed('action')
            ->exportable(false)
            ->printable(false)
            ->title(__('message.action'))
            ->width(60)
            ->addClass('text-center hide-search');

        return $columns;
    }
}
