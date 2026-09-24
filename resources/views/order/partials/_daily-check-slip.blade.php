@php
    $slipInvoiceDate = $slipInvoiceDate ?? now('Asia/Yangon')->format('d-m-Y');
    $slipRows = $slipRows ?? [];
    $slipTotals = $slipTotals ?? [
        'os_paid' => 0.0,
        'advance_paid' => 0.0,
        'item_value' => 0.0,
        'amount' => 0.0,
        'cust_paid' => 0.0,
        'balance' => 0.0,
    ];
@endphp

<div class="modal fade" id="dailyCheckShowSlipModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content pds-daily-check-slip-modal">
            <div class="modal-header pds-daily-check-slip-modal__header">
                <h5 class="modal-title mb-0">{{ __('message.show_slip') }}</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="pds-daily-check-slip" id="dailyCheckSlipCapture">
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <div class="font-weight-bold">{{ $slipCompany['name'] ?? 'Point Delivery' }}</div>
                            <div class="text-muted small">{{ $slipCompany['address'] ?? '' }}</div>
                            <div class="text-muted small">{{ $slipCompany['phone'] ?? '' }}</div>
                        </div>
                        <div class="col-md-4 text-md-right">
                            <div><strong>{{ __('message.invoice_date') }}</strong>: {{ $slipInvoiceDate }}</div>
                            <div><strong>{{ __('message.invoice_no') }}</strong>: {{ $invoiceNo ?? '-' }}</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div><strong>{{ __('message.sender') }}</strong>: {{ $slipSender['name'] ?? '-' }}</div>
                        <div><strong>{{ __('message.phone') }}</strong>: {{ $slipSender['phone'] ?? '-' }}</div>
                        <div><strong>{{ __('message.address') }}</strong>: {{ $slipSender['address'] ?? '-' }}</div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered pds-daily-check-slip-table mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('message.no') }}</th>
                                    <th>{{ __('message.date') }}</th>
                                    <th>{{ __('message.name') }}</th>
                                    <th>{{ __('message.phone') }}</th>
                                    <th>{{ __('message.township') }}</th>
                                    <th class="text-right">{{ __('message.os_paid') }}</th>
                                    <th class="text-right">{{ __('message.advance') }}</th>
                                    <th class="text-right">{{ __('message.item_value') }}</th>
                                    <th class="text-right">{{ __('message.amount') }}</th>
                                    <th class="text-right">{{ __('message.cust_paid') }}</th>
                                    <th class="text-right">{{ __('message.balance') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($slipRows as $index => $row)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $row['date'] }}</td>
                                        <td>
                                            {{ $row['name'] }}
                                            @if(! empty($row['is_kyo_shin']))
                                                <span class="pds-kyo-shin-row-badge">{{ __('message.kyo_shin_title') }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $row['phone'] }}</td>
                                        <td>{{ $row['township'] }}</td>
                                        <td class="text-right">{{ number_format($row['os_paid']) }}</td>
                                        <td class="text-right">{{ number_format($row['advance_paid']) }}</td>
                                        <td class="text-right">{{ number_format($row['item_value']) }}</td>
                                        <td class="text-right">{{ number_format($row['amount']) }}</td>
                                        <td class="text-right">{{ number_format($row['cust_paid']) }}</td>
                                        <td class="text-right">{{ number_format($row['balance']) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center text-muted">{{ __('message.no_record_found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if(count($slipRows) > 0)
                                <tfoot>
                                    <tr>
                                        <td colspan="5" class="text-right font-weight-bold">{{ __('message.total') }}</td>
                                        <td class="text-right font-weight-bold">{{ number_format($slipTotals['os_paid']) }}</td>
                                        <td class="text-right font-weight-bold">{{ number_format($slipTotals['advance_paid']) }}</td>
                                        <td class="text-right font-weight-bold">{{ number_format($slipTotals['item_value']) }}</td>
                                        <td class="text-right font-weight-bold">{{ number_format($slipTotals['amount']) }}</td>
                                        <td class="text-right font-weight-bold">{{ number_format($slipTotals['cust_paid']) }}</td>
                                        <td class="text-right font-weight-bold">{{ number_format($slipTotals['balance']) }}</td>
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
