<?php

namespace App\DataTables;

use App\Models\CustomerSupport;
use Illuminate\Support\Str;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use App\Traits\DataTableTrait;

class CustomerSupportDataTable extends DataTable
{
    use DataTableTrait;

    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->editColumn('created_at', function ($row) {
                return dateAgoFormate($row->created_at, true);
            })
            ->editColumn('support_type', function ($row) {
                $type = $row->support_type ?? '-';
                return '<span class="pds-support-type-badge">' . e($type) . '</span>';
            })
            ->editColumn('message', function ($row) {
                $message = $row->message ?? '-';
                return '<span class="pds-support-message-preview" title="' . e($message) . '">' . e(Str::limit($message, 60)) . '</span>';
            })
            ->editColumn('order_id', function ($row) {
                $order = $row->order_id;
                return $order ? '<a class="pds-support-order-link" href="' . route('order.show', $order) . '">#' . $order . '</a>' : '-';
            })
            ->editColumn('user_id', function ($row) {
                $user = $row->user;
                if (!$user) {
                    return '-';
                }

                $avatar = getSingleMedia($user, 'profile_image', null) ?: asset('images/default.png');

                return '<div class="pds-table-user">
                    <img src="' . $avatar . '" alt="" class="pds-table-user__avatar" loading="lazy">
                    <a href="' . route('users.show', $user->id) . '" class="pds-table-user__name">' . e($user->name) . '</a>
                </div>';
            })
            ->editColumn('id', function ($row) {
                return '<a class="pds-support-id-link" href="' . route('customersupport.show', $row->id) . '">#' . $row->id . '</a>';
            })
            ->addColumn('support_image', function ($row) {
                $imageurl = getSingleMediaCustomerSupport($row, 'support_image', true, false);
                return $imageurl
                    ? '<a href="' . $imageurl . '" class="image-popup-vertical-fit pds-support-media-thumb">
                        <img src="' . $imageurl . '" width="40" height="40" alt="support image"></a>'
                    : '-';
            })
            ->addColumn('support_videos', function ($row) {
                $videoUrl = getSingleMediaCustomerSupport($row, 'support_videos');
                return $videoUrl
                    ? '<a href="' . $videoUrl . '" target="_blank" class="pds-support-media-thumb">
                        <video src="' . $videoUrl . '" width="60" height="60"></video></a>'
                    : '-';
            })
            ->addColumn('action', function ($row) {
                return view('customer-suport.action', ['id' => $row->id])->render();
            })
            ->addIndexColumn()
            ->rawColumns(['action', 'id', 'support_image', 'support_videos', 'user_id', 'order_id', 'support_type', 'message']);
    }

    public function query(CustomerSupport $model)
    {
        return CustomerSupport::query()
            ->with('user')
            ->orderByDesc('created_at');
    }

    protected function getColumns()
    {
        return [
            Column::make('DT_RowIndex')
                ->searchable(false)
                ->title(__('message.srno'))
                ->orderable(false),
            ['data' => 'id', 'name' => 'id', 'title' => __('message.id')],
            ['data' => 'user_id', 'name' => 'user_id', 'title' => __('message.user'), 'class' => 'text-capitalize'],
            ['data' => 'message', 'name' => 'message', 'title' => __('message.message')],
            ['data' => 'support_type', 'name' => 'support_type', 'title' => __('message.support_type')],
            ['data' => 'order_id', 'name' => 'order_id', 'title' => __('message.order_id')],
            ['data' => 'support_image', 'name' => 'support_image', 'title' => __('message.image')],
            ['data' => 'support_videos', 'name' => 'support_videos', 'title' => __('message.videos')],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => __('message.created_at')],
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width(80)
                ->addClass('text-center hide-search'),
        ];
    }
}
