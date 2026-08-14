<?php

namespace App\DataTables;

use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

use App\Traits\DataTableTrait;

class NotificationDataTable extends DataTable
{
    use DataTableTrait;
    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable()
    {
        return datatables( $this->query() )
            ->addColumn('action', function($row){
                // $route = route('customer.show', $row->data['id']);
                // return '<a href="'.$route.'"><span class="badge bg-info mr-2">'.__('message.view').'</span></a>';
            })
            
            ->addColumn('category', function ($row) {
                $data = is_array($row->data) ? $row->data : [];
                return notificationCategoryLabel(inferNotificationCategory($data));
            })

            ->addColumn('message', function ($row) {
                return $row->data['message'] ?? '';
            })
            
            ->editColumn('created_at', function ($row) {
                return dateAgoFormate($row->created_at, true);
            })
            
            ->editColumn('updated_at', function ($row) {
                return dateAgoFormate($row->updated_at, true);
            })

            ->addIndexColumn()
            ->rawColumns(['action']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Notification $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        $userdata = auth()->user();
        $notifications = $userdata->notifications;
        $category = request()->get('category');

        if (!empty($category) && $category !== 'all') {
            $notifications = $notifications->filter(function ($notification) use ($category) {
                $data = is_array($notification->data) ? $notification->data : [];
                return inferNotificationCategory($data) === $category;
            });
        }

        return $this->applyScopes($notifications);
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        return [
            Column::make('DT_RowIndex')
                ->searchable(false)
                ->title(__('message.srno'))
                ->orderable(false)
                ->width(60),
            Column::make('category')->title(__('message.category'))->orderable(false)->searchable(false),
            Column::make('message')->title( __('message.message') ),
            Column::make('created_at')->title( __('message.created_at') ),
            Column::make('updated_at')->title( __('message.updated_at') ),
            Column::computed('action')
                  ->exportable(false)
                  ->printable(false)
                  ->width(60)
                  ->addClass('text-center'),
        ];
    }
}
