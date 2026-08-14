<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f97316; color: #fff; padding: 6px 4px; }
        td { padding: 5px 4px; border-bottom: 1px solid #e5e7eb; }
        tfoot td { background: #f97316; color: #fff; font-weight: bold; }
        .text-right { text-align: right; }
        .is-negative { color: #dc2626; font-weight: bold; }
        tfoot td.is-negative { color: #dc2626; }
        .header { margin-bottom: 12px; }
        .company { font-size: 14px; font-weight: bold; }
        .meta { font-size: 9px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company">{{ $slipCompany['name'] ?? 'Point Delivery' }}</div>
        <div class="meta">{{ $slipCompany['address'] ?? '' }}</div>
        <div class="meta">{{ $slipCompany['phone'] ?? '' }} | {{ $slipCompany['email'] ?? '' }}</div>
        <div class="meta">{{ __('message.invoice_date') }}: {{ $slipInvoiceDate }}</div>
        <div class="meta">{{ __('message.sender') }}: {{ $slipSender['name'] ?? '-' }} | {{ $slipSender['phone'] ?? '-' }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ __('message.no') }}</th>
                <th>{{ __('message.date') }}</th>
                <th>{{ __('message.customer') }}</th>
                <th>{{ __('message.phone') }}</th>
                <th>{{ __('message.address') }}</th>
                <th class="text-right">{{ __('message.item_value') }}</th>
                <th class="text-right">{{ __('message.deli_amount') }}</th>
                <th class="text-right">{{ __('message.gate_amount') }}</th>
                <th class="text-right">{{ __('message.os_to_pay') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($slipRows as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $row['date'] }}</td>
                    <td>{{ $row['customer'] }}</td>
                    <td>{{ $row['phone'] }}</td>
                    <td>{{ stringLong($row['address'], 'title', 20) ?: '-' }}</td>
                    <td class="text-right">{{ number_format($row['item_value']) }}</td>
                    <td class="text-right">{{ formatDeliAmountDisplayPlain($row['deli_amount_display'] ?? null, $row['deli_amount'] ?? 0) }}</td>
                    <td class="text-right">{{ $row['gate'] ?? number_format((float) ($row['gate_amount'] ?? 0)) }}</td>
                    <td class="text-right {{ !empty($row['os_to_pay_is_receive']) ? 'is-negative' : '' }}">{{ $row['os_to_pay_display'] }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5">{{ __('message.total') }}</td>
                <td class="text-right">{{ number_format($slipTotals['item_value']) }}</td>
                <td class="text-right">{{ formatDeliAmountDisplayPlain(null, $slipTotals['deli_amount'] ?? 0) }}</td>
                <td class="text-right">{{ number_format($slipTotals['gate_amount'] ?? 0) }}</td>
                <td class="text-right {{ ($slipTotals['os_to_pay'] ?? 0) < 0 ? 'is-negative' : '' }}">{{ number_format($slipTotals['os_to_pay']) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
