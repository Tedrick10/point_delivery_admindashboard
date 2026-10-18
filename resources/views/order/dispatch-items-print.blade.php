<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('message.order_detail_list') }} #{{ $order->id }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 16px;
            font-family: Arial, Helvetica, sans-serif;
            color: #111;
            background: #fff;
            font-size: 11px;
        }
        .pds-print-toolbar {
            display: flex;
            gap: 8px;
            margin-bottom: 12px;
        }
        .pds-print-toolbar button {
            border: 1px solid #d1d5db;
            background: #fff;
            border-radius: 8px;
            padding: 8px 14px;
            cursor: pointer;
            font-size: 13px;
        }
        .pds-print-toolbar button.primary {
            background: #f97316;
            border-color: #f97316;
            color: #fff;
        }
        h1 {
            margin: 0 0 4px;
            font-size: 18px;
        }
        .meta {
            margin: 0 0 12px;
            color: #444;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #fff7ed;
            font-weight: 700;
            white-space: nowrap;
        }
        td.num, th.num { text-align: right; }
        tfoot td {
            font-weight: 700;
            background: #fff7ed;
        }
        @media print {
            .pds-print-toolbar { display: none !important; }
            body { padding: 0; }
            th {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="pds-print-toolbar">
        <button type="button" class="primary" onclick="window.print()">{{ __('message.print') }}</button>
        <button type="button" onclick="window.close()">{{ __('message.close') }}</button>
    </div>

    <h1>{{ __('message.order_detail_list') }} — {{ __('message.order') }} #{{ $order->id }}</h1>
    <p class="meta">
        {{ __('message.os_name') }}: <strong>{{ $osName }}</strong>
        @if(!empty($branchName))
            · {{ __('message.to') }}: <strong>{{ $branchName }}</strong>
        @endif
        · {{ __('message.item_count') }}: <strong>{{ $items->count() }}</strong>
    </p>

    <table>
        <thead>
            <tr>
                <th>{{ __('message.no') }}</th>
                <th>{{ __('message.code') }}</th>
                <th>{{ __('message.status') }}</th>
                <th>{{ __('message.name') }}</th>
                <th>{{ __('message.phone') }}</th>
                <th>{{ __('message.township') }}</th>
                <th>{{ __('message.item_name') }}</th>
                <th>{{ __('message.size') }}</th>
                <th class="num">{{ __('message.item_value') }}</th>
                <th class="num">{{ __('message.deli_amount') }}</th>
                <th class="num">{{ __('message.cust_get') }}</th>
                <th class="num">{{ __('message.os_to_pay') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $index => $item)
                @php
                    $isDone = ! empty($item->admin_updated_at);
                    $remark = $isDone ? __('message.done') : '-';
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->code ?: '-' }}</td>
                    <td>{{ strtoupper((string) ($item->status ?: 'collected')) }}</td>
                    <td>{{ $item->customer_name ?: '-' }}</td>
                    <td>{{ $item->customer_phone ?: '-' }}</td>
                    <td>{{ $item->township ?: '-' }}</td>
                    <td>{{ $item->item_name ?: ($isDone ? __('message.done') : $remark) }}</td>
                    <td>{{ formatDispatchItemSize($item->weight) }}</td>
                    <td class="num">{{ number_format((float) ($item->item_value ?? 0)) }}</td>
                    <td class="num">{{ number_format((float) ($item->deli_amount ?? 0)) }}</td>
                    <td class="num">{{ number_format((float) ($item->cust_get ?? 0)) }}</td>
                    <td class="num">{{ number_format((float) $item->displayOsToPay()) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="12">{{ __('message.no_record_found') }}</td>
                </tr>
            @endforelse
        </tbody>
        @if($items->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="8">{{ __('message.total_amount') }}</td>
                    <td class="num">{{ number_format((float) $items->sum('item_value')) }}</td>
                    <td class="num">{{ number_format((float) $items->sum('deli_amount')) }}</td>
                    <td class="num">{{ number_format((float) $items->sum('cust_get')) }}</td>
                    <td class="num">{{ number_format((float) $items->sum(fn ($i) => $i->displayOsToPay())) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    @if(!empty($autoprint))
        <script>
            window.addEventListener('load', function () {
                setTimeout(function () { window.print(); }, 250);
            });
        </script>
    @endif
</body>
</html>
