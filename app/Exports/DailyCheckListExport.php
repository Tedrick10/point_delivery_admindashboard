<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DailyCheckListExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected Collection $rows;

    protected string $mode;

    protected int $counter = 1;

    public function __construct(Collection $rows, string $mode = 'os')
    {
        $this->rows = $rows;
        $this->mode = $mode;
    }

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'No.',
            'ReceivedDate',
            'Name',
            'Invoice No',
            'Item Count',
            'Advance Paid',
            'Amount',
            'OsToPay',
            'Deli Amount',
            'Gate',
            'User',
            'Date',
            'Remitted Date',
        ];
    }

    public function map($row): array
    {
        return [
            $this->counter++,
            $row->received_date ?? '-',
            $row->name ?? '-',
            $row->invoice_no ?? '-',
            $row->item_count ?? 0,
            number_format((float) ($row->advance_paid ?? 0)),
            number_format((float) ($row->amount ?? 0)),
            $row->os_to_pay_display ?? number_format((float) ($row->os_to_pay ?? 0)),
            number_format((float) ($row->deli_amount ?? 0)),
            number_format((float) ($row->gate ?? 0)),
            $row->user_name ?? '-',
            $row->modified_date ?? '-',
            $row->remitted_date ?: '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
