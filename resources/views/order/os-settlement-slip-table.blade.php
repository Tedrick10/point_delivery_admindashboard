@php
    $standalone = $standalone ?? false;
@endphp
@if($standalone)
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OS Settlement Slip</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 16px; color: #1f2937; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th { background: #f97316; color: #fff; padding: 8px 6px; text-align: left; white-space: normal; line-height: 1.25; overflow: hidden; }
        td { padding: 7px 6px; border-bottom: 1px solid #e5e7eb; }
        tr:nth-child(even) td { background: #f9fafb; }
        tfoot td { background: #f97316; color: #fff; font-weight: bold; }
        .text-right { text-align: right; }
        .is-negative { color: #dc2626; }
        .meta { font-size: 11px; color: #6b7280; }
        .company { font-size: 16px; font-weight: bold; }
        .top { display: flex; justify-content: space-between; margin-bottom: 12px; }
        .sender { margin-bottom: 12px; font-size: 12px; }
        .kpay-block { margin-top: 24px; border-top: 2px solid #f97316; padding-top: 16px; }
        .kpay-block img { max-width: 100%; max-height: 480px; border: 1px solid #e5e7eb; border-radius: 8px; }
        .pds-deli-amount-cell {
            display: inline-flex !important;
            flex-direction: row !important;
            flex-wrap: nowrap !important;
            align-items: baseline !important;
            justify-content: flex-end !important;
            gap: 6px !important;
            width: 100%;
            white-space: nowrap !important;
            position: static !important;
        }
        .pds-deli-amount-price,
        .pds-deli-amount-type {
            display: inline-block !important;
            position: static !important;
            float: none !important;
            margin: 0 !important;
            transform: none !important;
            line-height: 1.35 !important;
        }
        .pds-deli-amount-price {
            flex: 1 1 auto !important;
            font-variant-numeric: tabular-nums;
            font-weight: 800;
            text-align: right;
        }
        .pds-deli-amount-type {
            flex: 0 0 4.75rem !important;
            width: 4.75rem !important;
            text-align: left !important;
            color: #64748b;
            font-weight: 600;
            font-size: 0.85em;
        }
        tfoot .pds-deli-amount-type { color: rgba(255,255,255,0.85); }
        .pds-deli-amount-type--empty { visibility: hidden; }
        th.text-right, td.text-right { text-align: right; }
        th .pds-deli-amount-price { color: #fff; font-weight: 800; text-transform: uppercase; }
    </style>
</head>
<body>
@endif

<div class="pds-os-slip">
    <div class="top">
        <div>
            <div class="company">{{ $slipCompany['name'] ?? 'Point Delivery' }}</div>
            <div class="meta">{{ $slipCompany['address'] ?? '' }}</div>
            <div class="meta">{{ $slipCompany['phone'] ?? '' }}</div>
            <div class="meta">{{ $slipCompany['email'] ?? '' }}</div>
        </div>
        <div>
            <div class="meta">{{ __('message.invoice_date') }}</div>
            <strong>{{ $slipInvoiceDate }}</strong>
        </div>
    </div>

    <div class="sender">
        <div><strong>{{ __('message.sender') }}:</strong> {{ $slipSender['name'] ?? '-' }}</div>
        <div><strong>{{ __('message.phone') }}:</strong> {{ $slipSender['phone'] ?? '-' }}</div>
        <div><strong>{{ __('message.address') }}:</strong> {{ $slipSender['address'] ?? '-' }}</div>
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
                <th class="text-right">
                    <span class="pds-deli-amount-cell pds-deli-amount-cell--header">
                        <span class="pds-deli-amount-price">{{ __('message.deli_amount') }}</span>
                        <span class="pds-deli-amount-type pds-deli-amount-type--empty">&nbsp;</span>
                    </span>
                </th>
                <th class="text-right">{{ __('message.gate_amount') }}</th>
                <th class="text-right">{{ __('message.os_to_pay') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($slipRows as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $row['date'] }}</td>
                    <td>{{ $row['customer'] }}</td>
                    <td>{{ $row['phone'] }}</td>
                    <td>{{ stringLong($row['address'], 'title', 28) ?: '-' }}</td>
                    <td class="text-right">{{ number_format($row['item_value']) }}</td>
                    <td class="text-right">{!! formatDeliAmountDisplayHtml($row['deli_amount_display'] ?? null, $row['deli_amount'] ?? 0) !!}</td>
                    <td class="text-right">{{ $row['gate'] ?? number_format((float) ($row['gate_amount'] ?? 0)) }}</td>
                    <td class="text-right {{ $row['os_to_pay_is_receive'] ? 'is-negative' : '' }}">{{ $row['os_to_pay_display'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align:center;">{{ __('message.no_record_found') }}</td>
                </tr>
            @endforelse
        </tbody>
        @if(count($slipRows) > 0)
            <tfoot>
                <tr>
                    <td colspan="5">{{ __('message.total') }}</td>
                    <td class="text-right">{{ number_format($slipTotals['item_value']) }}</td>
                    <td class="text-right">{!! formatDeliAmountDisplayHtml(null, $slipTotals['deli_amount'] ?? 0) !!}</td>
                    <td class="text-right">{{ number_format($slipTotals['gate_amount'] ?? 0) }}</td>
                    <td class="text-right {{ $slipTotals['os_to_pay'] < 0 ? 'is-negative' : '' }}">{{ number_format($slipTotals['os_to_pay']) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    @if(!empty($kpayImageUrl))
        <div class="kpay-block">
            <strong>{{ __('message.kpay_slip') }}</strong>
            <div style="margin-top:8px;">
                <img src="{{ $kpayImageUrl }}" alt="KBZ Pay Slip">
            </div>
        </div>
    @endif
</div>

@if($standalone)
</body>
</html>
@endif
