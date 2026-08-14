<?php

namespace App\DataTables;

use App\Models\AdminLoginDevice;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use App\Traits\DataTableTrait;
use Illuminate\Support\Facades\Auth;

class AdminLoginDeviceDataTable extends DataTable
{
    use DataTableTrait;

    /**
     * Build DataTable class.
     */
    public function dataTable($query)
    {
        $auth_user = Auth::user();

        return datatables()
            ->eloquent($query)
            ->addColumn('action', function ($row) use ($auth_user) {
                $id = $row->id;
                $action_type = 'action';
                $user_id = $row->user_id;
            return view('admin-device.action', compact('row', 'id', 'auth_user', 'action_type','user_id'))->render();
            })
            ->editColumn('created_at', function ($row) {
                return dateAgoFormate($row->created_at, true);
            })
            ->editColumn('login_at', function ($row) {
                return dateAgoFormate($row->login_at, true);
            })
            ->editColumn('ip_address', function ($row) {
                $currentIp = request()->ip();
                $ip = is_array($row) ? ($row['ip_address'] ?? null) : $row->ip_address ?? null;

                if ($ip && $ip === $currentIp) {
                    return __('message.your_ip_address', ['ip' => e($ip)]);
                }
                return maskSensitiveInfo('ip_address', $ip);
            })
            ->editColumn('user_id', function($row) {
                return optional($row->user)->name ?? '-';
            })
            ->addColumn('city', function ($row) {
                return optional($row->latestLoginHistory)->city ?? '-';
            })
            ->addColumn('region', function ($row) {
                return optional($row->latestLoginHistory)->region ?? '-';
            })
            ->addColumn('country', function ($row) {
                return optional($row->latestLoginHistory)->country ?? '-';
            })
            ->addColumn('browser', function ($row) {
                return optional($row->latestLoginHistory)->browser ?? '-';
            })
            ->addIndexColumn()
            ->rawColumns(['action', 'ip_address']);
    }

    /**
     * Get query source of dataTable.
     */
    public function query(AdminLoginDevice $model)
    {
    return $model->with(['user:id,name', 'latestLoginHistory'])->where('is_active', 1)->newQuery();
    }

    protected function getColumns()
    {
        return [
            Column::make('DT_RowIndex')
                ->searchable(false)
                ->title(__('message.srno'))
                ->orderable(false)
                ->width(60),
            ['data' => 'user_id', 'name' => 'user.name', 'title' => __('message.user')],
            ['data' => 'ip_address', 'name' => 'ip_address', 'title' => __('message.ip_address')],
            ['data' => 'city', 'name' => 'city', 'title' => __('message.city')],
            ['data' => 'region', 'name' => 'region', 'title' => __('message.region')],
            ['data' => 'country', 'name' => 'country', 'title' => __('message.country')],
            ['data' => 'browser', 'name' => 'browser', 'title' => __('message.browser')],
            ['data' => 'login_at', 'name' => 'login_at', 'title' => __('message.login_at')],
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->title(__('message.action'))
                ->width(60)
                ->addClass('text-center hide-search'),
        ];
    }
}
