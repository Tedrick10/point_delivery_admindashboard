<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DispatchRiderItemsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
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
            'ID',
            'Order',
            'Received Date',
            'Voucher Code',
            'Status',
            'Online Shop',
            'OS Phone',
            'OS Address',
            'Customer Name',
            'Phone',
            'Township',
            'Address',
            'OS Paid',
            'Advance Paid',
            'Item Value',
            'Deli Amount',
            'Cust Get',
            'Cust Paid',
            'Gate',
            'OS To Pay',
            'Remark',
            'Kyo Shin',
        ];
    }

    public function map($row): array
    {
        $order = $row->order;
        $osName = function_exists('resolveDispatchOsName')
            ? resolveDispatchOsName($order)
            : (string) ($order->client->name ?? '-');
        $osPhone = function_exists('resolveDispatchOsPhone')
            ? resolveDispatchOsPhone($order)
            : (string) ($order->client->contact_number ?? '-');
        $osAddress = function_exists('resolveDispatchOsAddress')
            ? resolveDispatchOsAddress($order)
            : (string) ($order->client->address ?? '-');

        $township = \App\Models\DispatchOrderItem::deliveryCityLabel($row->delivery_city);
        if ($township === '-' && ! empty($row->township)) {
            $township = (string) $row->township;
        }

        $orderDate = $order?->created_at
            ? \Carbon\Carbon::parse($order->created_at)->format('d-m-Y')
            : '-';
        $receivedDate = function_exists('formatDispatchYangonDate')
            ? formatDispatchYangonDate($row->received_date)
            : ($row->received_date
                ? \Carbon\Carbon::parse($row->received_date)->format('d-m-Y')
                : '-');

        $remarkParts = [];
        if ($row->relationLoaded('pendingRemarks') && $row->pendingRemarks) {
            foreach ($row->pendingRemarks as $history) {
                $text = trim((string) ($history->remark ?? ''));
                if ($text !== '') {
                    $remarkParts[] = $text;
                }
            }
        }
        if ($remarkParts === []) {
            $fallback = trim((string) ($row->remark ?? ''));
            if ($fallback !== '') {
                $remarkParts[] = $fallback;
            }
        }

        $osToPay = method_exists($row, 'displayOsToPay')
            ? (float) $row->displayOsToPay()
            : (float) ($row->os_to_pay ?? 0);

        return [
            $this->counter++,
            $row->id,
            $orderDate,
            $receivedDate !== '' ? $receivedDate : '-',
            (string) ($row->code ?? '-'),
            strtoupper((string) ($row->status ?: '-')),
            $osName !== '' ? $osName : '-',
            $osPhone !== '-' ? $osPhone : '',
            $osAddress !== '-' ? $osAddress : '',
            (string) ($row->customer_name ?: '-'),
            (string) ($row->customer_phone ?: '-'),
            $township !== '' ? $township : '-',
            (string) ($row->customer_address ?: '-'),
            (float) ($row->os_paid ?? 0),
            (float) ($row->advance_paid ?? 0),
            (float) ($row->item_value ?? 0),
            (float) ($row->deli_amount ?? 0),
            (float) ($row->cust_get ?? 0),
            (float) ($row->cust_get ?? 0),
            (float) ($row->gate_amount ?? 0),
            $osToPay,
            $remarkParts !== [] ? implode(' | ', $remarkParts) : '-',
            $row->kyoShinItem ? 'Yes' : '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
