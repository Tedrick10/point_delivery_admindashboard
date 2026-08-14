<?php

namespace App\DataTables;

use App\Models\DeliveryManPayoutReport;
use App\Traits\DataTableTrait;
use Carbon\Carbon;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class DeliveryManPayoutReportsDataTable extends DataTable
{
    use DataTableTrait;

    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)

            ->editColumn('delivery_man_id', function ($report) {
                 return '<a href="'.route('deliveryman.show',$report->delivery_man_id).'">'.($report->driver)->name.'</span></a>';
            })

            ->editColumn('week', function ($report) {
                return Carbon::parse($report->week_start_date)->format('M j, Y') .' - '. Carbon::parse($report->week_end_date)->format('M j, Y');
            })
            ->editColumn('document', function ($report) {
                if ($report->is_mail_sent != 1) {
                    return '-';
                }

                $fileUrl = getSingleMedia($report, 'delivery_man_payout_report_document', null);
                if (!$fileUrl || str_contains($fileUrl, 'default.png')) {
                    return '-';
                }

                return '<a href="'.$fileUrl.'" target="_blank" title="View Document"><i class="fas fa-file-pdf text-danger" style="font-size:18px;"></i></a>';
            })

            ->editColumn('status', function ($report) {

                $status = strtolower($report->status);

                $class = match ($status) {
                    'pending'  => 'badge-danger',
                    'paid'     => 'badge-success',
                    'progress' => 'badge-warning',
                    default    => 'badge-secondary',
                };

                return '<span class="badge '.$class.'">'.ucfirst($status).'</span>';
            })

            ->editColumn('generated_at', function ($report) {
                return dateAgoFormate($report->generated_at, true);
            })

            ->editColumn('paid_at', function ($report) {
                return $report->paid_at ? dateAgoFormate($report->paid_at, true) : '-';
            })
            ->editColumn('driver_tips', function ($report) {
                return $report->driver_tips ? $report->driver_tips : '-';
            })
            ->editColumn('payment_method', function ($report) {
                return $report->payment_method ? $report->payment_method : '-';
            })
            ->editColumn('transaction_reference', function ($report) {
                return $report->transaction_reference ? $report->transaction_reference : '-';
            })

            ->addIndexColumn()
            ->addColumn('action', function($data){
                if ($data->status === 'paid') {
                    return ''; // no buttons
                }
                $id = $data->id;
                return view('delivery_man_payout_report.action',compact('data','id'))->render();
            })

            ->order(function ($query) {
                if (request()->has('order')) {
                    $order = request()->order[0];
                    $columnIndex = $order['column'];
                    $direction = 'desc';
                    $column_name = 'generated_at';
                    if( $columnIndex != 0) {
                        $column_name = request()->columns[$columnIndex]['data'];
                        $direction = $order['dir'];
                    }
                    $query->orderBy($column_name, $direction);
                }
            })
            ->rawColumns(['action','status','delivery_man_id','document']);
    }

    /**
     * Query
     */
    public function query(DeliveryManPayoutReport $model)
    {
        $query = $model->newQuery()->with('driver');
        if ($this->delivery_man_id) {
            $query->where('delivery_man_id', $this->delivery_man_id);
        }

        return $query;
    }

    /**
     * Columns
     */
    protected function getColumns()
    {
        $columns = [
            Column::make('DT_RowIndex')
                ->searchable(false)
                ->title(__('message.srno'))
                ->orderable(false),
            ['data' => 'id', 'name' => 'id', 'title' => __('message.id')],
            ['data' => 'delivery_man_id', 'name' => 'delivery_man_id', 'title' => __('message.delivery_man')],
            ['data' => 'week', 'name' => 'week', 'title' => __('message.week')],
            ['data' => 'total_fare', 'name' => 'total_fare', 'title' => __('message.total_fare')],
            ['data' => 'total_commission', 'name' => 'total_commission', 'title' => __('message.total_commission')],
            ['data' => 'total_trips', 'name' => 'total_trips', 'title' => __('message.total_trips')],
            ['data' => 'payout_amount', 'name' => 'payout_amount', 'title' => __('message.payout_amount')],
            ['data' => 'driver_tips', 'name' => 'driver_tips', 'title' => __('message.driver_tips'), 'orderable' => false, 'searchable' => false],
            ['data' => 'document', 'name' => 'document', 'title' => __('message.document_mail'), 'orderable' => false, 'searchable' => false],
            ['data' => 'payment_method', 'name' => 'payment_method', 'title' => __('message.payment_method'), 'orderable' => false, 'searchable' => false],
            ['data' => 'transaction_reference', 'name' => 'transaction_reference', 'title' => __('message.transaction_reference'), 'orderable' => false, 'searchable' => false],
            ['data' => 'status', 'name' => 'status', 'title' => __('message.status')],
            ['data' => 'generated_at', 'name' => 'generated_at', 'title' => __('message.generated_at')],
            ['data' => 'paid_at', 'name' => 'paid_at', 'title' => __('message.paid_at')],
        ];

        if (!($this->delivery_man_id)) {
            $columns[] = Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width(60)
                ->addClass('text-center hide-search');
        }

        return $columns;
    }
}
