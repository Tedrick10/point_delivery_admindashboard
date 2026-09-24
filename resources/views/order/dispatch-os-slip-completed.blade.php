@php
    $slipInvoiceDate = $filterToDate ?? $filterFromDate ?? \Carbon\Carbon::now('Asia/Yangon')->format('d-m-Y');
    $slipRows = $slipRows ?? [];
    $slipTotals = $slipTotals ?? [
        'os_paid' => 0.0,
        'item_value' => 0.0,
        'deli_amount' => 0.0,
        'os_to_pay' => 0.0,
    ];
@endphp

<div class="modal fade" id="osSlipCompletedModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content pds-os-slip-modal">
            <div class="modal-header pds-os-slip-modal__header">
                <h5 class="modal-title">{{ __('message.show_slip_completed') }}</h5>
                <div class="pds-os-slip-modal__header-actions">
                    <button type="button" class="pds-os-slip-screenshot-btn" id="osSlipScreenshotBtn">
                        <i class="fas fa-download" aria-hidden="true"></i>
                        <span>{{ __('message.download') }}</span>
                    </button>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>
            <div class="modal-body pds-os-slip-modal__body">
                <div class="pds-os-slip" id="osSlipCompletedCapture">
                    <div class="pds-os-slip__top">
                        <div class="pds-os-slip__brand">
                            <div class="pds-os-slip__logo-wrap">
                                @if(!empty($slipCompany['logo']))
                                    <img src="{{ $slipCompany['logo'] }}" alt="Point" class="pds-os-slip__logo">
                                @else
                                    <div class="pds-os-slip__logo-fallback">point</div>
                                @endif
                            </div>
                            <div class="pds-os-slip__brand-copy">
                                <div class="pds-os-slip__company">{{ $slipCompany['name'] }}</div>
                                <div class="pds-os-slip__meta">{{ $slipCompany['address'] }}</div>
                                <div class="pds-os-slip__meta">{{ $slipCompany['phone'] }}</div>
                                <div class="pds-os-slip__meta">{{ $slipCompany['email'] }}</div>
                            </div>
                        </div>
                        <div class="pds-os-slip__invoice">
                            <div class="pds-os-slip__invoice-label">{{ __('message.invoice_date') }}</div>
                            <div class="pds-os-slip__invoice-value">{{ $slipInvoiceDate }}</div>
                        </div>
                    </div>

                    <div class="pds-os-slip__sender">
                        <div><strong>{{ __('message.sender') }}:</strong> {{ $slipSender['name'] }}</div>
                        <div><strong>{{ __('message.phone') }}:</strong> {{ $slipSender['phone'] }}</div>
                        <div><strong>{{ __('message.address') }}:</strong> {{ $slipSender['address'] }}</div>
                    </div>

                    <div class="pds-os-slip__table-wrap">
                        <table class="pds-os-slip__table">
                            <colgroup>
                                <col class="pds-os-slip__col--no">
                                <col class="pds-os-slip__col--date">
                                <col class="pds-os-slip__col--customer">
                                <col class="pds-os-slip__col--phone">
                                <col class="pds-os-slip__col--address">
                                <col class="pds-os-slip__col--item">
                                <col class="pds-os-slip__col--deli">
                                <col class="pds-os-slip__col--gate">
                                <col class="pds-os-slip__col--os">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th class="text-center">{{ __('message.no') }}</th>
                                    <th>{{ __('message.date') }}</th>
                                    <th>{{ __('message.customer') }}</th>
                                    <th>{{ __('message.phone') }}</th>
                                    <th>{{ __('message.address') }}</th>
                                    <th class="text-right">{{ __('message.item_value') }}</th>
                                    <th class="text-right pds-os-slip__col-deli">
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
                                        <td class="text-center">{{ $index + 1 }}</td>
                                        <td>{{ $row['date'] }}</td>
                                        <td>
                                            {{ $row['customer'] }}
                                            @include('order.partials._slip-kyo-shin-badge', ['row' => $row])
                                        </td>
                                        <td>{{ $row['phone'] }}</td>
                                        <td title="{{ $row['address'] }}">{{ stringLong($row['address'], 'title', 36) ?: '-' }}</td>
                                        <td class="text-right">{{ number_format($row['item_value']) }}</td>
                                        <td class="text-right pds-os-slip__col-deli">{!! formatDeliAmountDisplayHtml($row['deli_amount_display'] ?? null, $row['deli_amount'] ?? 0) !!}</td>
                                        <td class="text-right">{{ $row['gate'] ?? number_format((float) ($row['gate_amount'] ?? 0)) }}</td>
                                        <td class="text-right {{ !empty($row['exclude_from_settlement_amount']) ? '' : ($row['os_to_pay_is_receive'] ? 'is-negative' : '') }}">
                                            @if(!empty($row['exclude_from_settlement_amount']))
                                                <span class="pds-slip-kyo-shin-badge" style="display:inline-block;padding:1px 7px;border-radius:999px;background:#ffedd5;color:#c2410c;font-size:10px;font-weight:800;">{{ __('message.kyo_shin_title') }}</span>
                                            @else
                                                {{ $row['os_to_pay_display'] }}
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">{{ __('message.no_record_found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if(count($slipRows) > 0)
                                <tfoot>
                                    <tr>
                                        <td colspan="5" class="pds-os-slip__total-label">{{ __('message.total') }}</td>
                                        <td class="text-right">{{ number_format($slipTotals['item_value']) }}</td>
                                        <td class="text-right pds-os-slip__col-deli">{!! formatDeliAmountDisplayHtml(null, $slipTotals['deli_amount'] ?? 0) !!}</td>
                                        <td class="text-right">{{ number_format($slipTotals['gate_amount'] ?? 0) }}</td>
                                        <td class="text-right {{ $slipTotals['os_to_pay'] < 0 ? 'is-negative' : '' }}">{{ number_format($slipTotals['os_to_pay']) }}</td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
