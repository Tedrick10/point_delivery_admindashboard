<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DispatchOrderItemsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected Collection $rows;

    protected int $counter = 1;

    public function __construct(Collection $rows)
    {
        $this->rows = $rows;
    }

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'No.',
            'Item ID',
            'Received Date',
            'Modified Date',
            'Code',
            'Status',
            'To',
            'Name',
            'Phone',
            'Address',
            'Township',
            'Item Name',
            'Remark',
            'Size',
            'Advance Paid',
            'Item Value',
            'Deli Amount',
            'Cust Get',
            'Os To Pay',
        ];
    }

    public function map($row): array
    {
        $remark = trim((string) ($row->remark ?? ''));
        $normalized = strtolower(preg_replace('/\s+/', '_', $remark) ?? '');
        if ($remark === '' || $remark === '-' || $normalized === 'rider_added' || $normalized === 'rider_done') {
            $remark = __('message.done');
        }

        return [
            $this->counter++,
            $row->id,
            function_exists('formatDispatchYangonDate')
                ? formatDispatchYangonDate($row->received_date)
                : (string) ($row->received_date ?? '-'),
            function_exists('formatDispatchYangonDate')
                ? formatDispatchYangonDate($row->updated_at)
                : (string) ($row->updated_at ?? '-'),
            (string) ($row->code ?? '-'),
            strtoupper((string) ($row->status ?: 'collected')),
            (string) ($row->toBranch->name ?? '-'),
            (string) ($row->customer_name ?? '-'),
            (string) ($row->customer_phone ?? '-'),
            (string) ($row->customer_address ?? '-'),
            (string) ($row->township ?? '-'),
            (string) ($row->item_name ?? '-'),
            $remark,
            function_exists('formatDispatchItemSize')
                ? formatDispatchItemSize($row->weight)
                : (string) ($row->weight ?? '-'),
            number_format((float) ($row->advance_paid ?? 0)),
            number_format((float) ($row->item_value ?? 0)),
            number_format((float) ($row->deli_amount ?? 0)),
            number_format((float) ($row->cust_get ?? 0)),
            number_format((float) (method_exists($row, 'displayOsToPay') ? $row->displayOsToPay() : ($row->os_to_pay ?? 0))),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
