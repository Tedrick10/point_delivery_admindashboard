<?php

namespace App\DataTables;

use App\Models\User;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use App\Traits\DataTableTrait;

class ClientDataTable extends DataTable
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
            ->editColumn('approval_status', function ($data) {
                $action_type = 'approval_status';
                $deleted_at = null;
                return view('users.action', compact('data', 'action_type', 'deleted_at'))->render();
            })
            ->editColumn('created_at', function ($query) {
                return dateAgoFormate($query->created_at, true);
            })
            ->editColumn('last_actived_at', function ($query) {
                return dateAgoFormate($query->last_actived_at, true) ?? '-';
            })
            ->editColumn('contact_number', function($query) {
                return auth()->user()->hasRole('admin') ? maskSensitiveInfo('contact_number', $query->contact_number) : maskSensitiveInfo('contact_number', $query->contact_number);
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
            ->editColumn('city_id', function ($query) {
                return optional($query->city)->name ?? '-';
            })
            ->editColumn('country_id', function ($query) {
                return optional($query->country)->name ?? '-';
            })
            ->addColumn('name', function ($row) {
                $img = getSingleMedia($row, 'profile_image');
                $fallback = asset('images/user/1.jpg');
                $name = e($row->name);
                $url = route('users.show', $row->id);
                $phone = e(maskSensitiveInfo('contact_number', $row->contact_number) ?: '-');

                return '<div class="pds-table-user">'
                    . '<img src="' . e($img ?: $fallback) . '" alt="" class="pds-table-user__avatar">'
                    . '<div class="pds-table-user__meta">'
                    . '<a href="' . $url . '" class="pds-table-user__name">' . $name . '</a>'
                    . '<span class="pds-table-user__sub">' . $phone . '</span>'
                    . '</div>'
                    . '</div>';
            })
            ->filterColumn('name', function ($query, $keyword) {
                $query->where('name', 'like', "%{$keyword}%");
            })
            ->editColumn('otp_verify_at', function ($data) {
                if ($data->otp_verify_at !== null) {
                    return '<p class="text-capitalize badge bg-success text-center mt-3"> Verified</p>';
                }else{

                    $action_type = 'verify';
                    $deleted_at = null;
                    return view('users.action', compact('data', 'action_type', 'deleted_at'))->render();
                }
            })
            ->addColumn('action', function ($row) {
                $id = $row->id;
                $action_type = 'action';
                $deleted_at = $row->deleted_at;
                return view('users.action', compact('id', 'deleted_at', 'action_type'))->render();
            })
            ->addIndexColumn()
            ->editColumn('is_autoverified_email', function ($data) {
                $user = $data->whereNotNull('email_verified_at')->get();
                if ($user) {
                    return '<div class="custom-switch custom-switch-text">
                            <input type="checkbox" class="custom-control-input change_user_verification"
                                data-type="user" data-name="is_autoverified_email" id="email_' . $data->id . '"
                                data-id="' . $data->id . '" ' . ($data->email_verified_at ? 'checked' : '') . ' value="' . $data->id . '">
                            <label class="custom-control-label" for="email_' . $data->id . '" data-on-label="Yes" data-off-label="No"></label>
                        </div>';

                } else {
                    $action_type = 'email_verified';
                    $deleted_at = null;
                    return view('users.action', compact('data', 'action_type', 'deleted_at'))->render();
                }
            })
            ->editColumn('is_autoverified_mobile', function ($data) {
                $user = $data->whereNotNull('otp_verify_at')->get();
                if ($user) {
                    return '<div class="custom-switch custom-switch-text">
                                <input type="checkbox" class="custom-control-input change_user_verification"
                                    data-type="user" data-name="is_autoverified_mobile" id="mobile_' . $data->id . '"
                                    data-id="' . $data->id . '" ' . ($data->otp_verify_at != NULL ? 'checked' : '') . ' value="' . $data->id . '">
                                <label class="custom-control-label" for="mobile_' . $data->id . '" data-on-label="Yes" data-off-label="No"></label>
                            </div>';
                } else {
                    $action_type = 'mobile_verified';
                    $deleted_at = null;
                    return view('users.action', compact('data', 'action_type', 'deleted_at'))->render();
                }
            })
            ->rawColumns(['checkbox', 'action', 'approval_status','name','otp_verify_at','is_autoverified_mobile','is_autoverified_email']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\User $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(User $model)
    {
        $model = User::whereIn('user_type', ['client']);
        applyClientBranchScope($model, auth()->user(), (int) ($this->branch_id ?? request('branch_id', 0)));
        $city = request()->input('city_id');
        $country = request()->input('country_id');
        $lastActive = request()->input('last_actived_at');
        $status = request('status');
        switch ($status) {
            case 'active':
            case 'approved':
                $model = $model->where('approval_status', User::APPROVAL_APPROVED);
                break;
            case 'inactive':
            case 'rejected':
                $model = $model->where('approval_status', User::APPROVAL_REJECTED);
                break;
            case 'pending':
                $model = $model->where('approval_status', User::APPROVAL_PENDING);
                break;
            default:
                $model = $model->where('approval_status', User::APPROVAL_PENDING);
                break;
        }

        if ($city) {
            $model->where('city_id', $city);
        }
        if ($country) {
            $model->where('country_id', $country);
        }
        if ($lastActive) {
            if ($lastActive === 'active_user') {
                $model->where('last_actived_at', '<', now())
                ->where('last_actived_at', '>', now()->subDays(6));
            } elseif ($lastActive === 'engaged_user') {
                $model->where('last_actived_at', '<', now()->subDays(6))
                      ->where('last_actived_at', '>', now()->subDays(15));
            } elseif ($lastActive === 'inactive_user') {
                $model->where('last_actived_at', '<=', now()->subDays(15))
                ->orWhereNull('last_actived_at');
            }
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
            ['data' => 'name', 'name' => 'name', 'title' => __('message.name'),  'class' => 'text-capitalize', 'orderable' => false],
            ['data' => 'city_id', 'name' => 'city_id', 'title' => __('message.city')],
            ['data' => 'country_id', 'name' => 'country_id', 'title' => __('message.country')],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => __('message.created_at')],
            ['data' => 'last_actived_at', 'name' => 'last_actived_at', 'title' => __('message.last_active')],
            Column::make('approval_status', 'approval_status')
                ->title(__('message.status'))
                ->visible(true)
                ->orderable(false),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->title(__('message.action'))
                ->width(100)
                ->addClass('text-center hide-search'),
        ];

        return $columns;
    }

    public function getBuilderParameters(): array
    {
        $params = parent::getBuilderParameters();
        $params['scrollX'] = false;
        $params['autoWidth'] = false;

        return $params;
    }
}
