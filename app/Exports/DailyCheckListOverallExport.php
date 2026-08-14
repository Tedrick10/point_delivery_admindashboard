<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DailyCheckListOverallExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected Collection $groups;

    protected int $counter = 1;

    public function __construct(Collection $groups)
    {
        $this->groups = $groups;
    }

    public function collection()
    {
        $flat = collect();
        foreach ($this->groups as $group) {
            foreach ($group->items as $item) {
                $flat->push((object) [
                    'invoice_no' => $group->invoice->invoice_no,
                    'party_name' => $group->party_name,
                    'party_type' => $group->invoice->party_type,
                    'received_date' => $group->invoice->received_date?->format('d-m-Y'),
                    'remitted_date' => $group->invoice->remitted_date?->format('d-m-Y'),
                    'item' => $item,
                ]);
            }
        }

        return $flat;
    }

    public function headings(): array
    {
        return [
            'No.',
            'Invoice No',
            'Party',
            'Type',
            'ReceivedDate',
            'Remitted Date',
            'Id',
            'Voucher Code',
            'Status',
            'From - To',
            'OS Name',
            'Customer',
            'Phone',
            'Address',
            'OS Paid',
            'Advance Paid',
            'Item Value',
            'Deli Amount',
            'Cust Get',
            'Cust Paid',
            'Os Amount',
            'Office',
            'Gate',
            'OsToPay',
        ];
    }

    public function map($row): array
    {
        $item = $row->item;
        $fromName = optional($item->fromBranch)->name ?: '-';
        $toName = optional($item->toBranch)->name ?: '-';
        $fromTo = ($fromName === '-' && $toName === '-') ? '-' : ($fromName.' - '.$toName);
        $osName = function_exists('resolveDispatchOsName')
            ? resolveDispatchOsName($item->order)
            : (optional($item->order?->client)->name ?: '-');
        $status = app(\App\Services\DailyCheckListService::class)->itemStatusLabel($item);

        return [
            $this->counter++,
            $row->invoice_no,
            $row->party_name,
            strtoupper((string) $row->party_type),
            $row->received_date ?? '-',
            $row->remitted_date ?: '-',
            $item->id,
            $item->code ?: '-',
            $status,
            $fromTo,
            $osName,
            $item->customer_name ?: '-',
            $item->customer_phone ?: '-',
            $item->customer_address ?: '-',
            number_format((float) ($item->os_paid ?? 0)),
            number_format((float) ($item->advance_paid ?? 0)),
            number_format((float) ($item->item_value ?? 0)),
            number_format((float) ($item->deli_amount ?? 0)),
            number_format((float) ($item->cust_get ?? 0)),
            '0',
            '0',
            '0',
            '0',
            number_format((float) $item->displayOsToPay()),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
