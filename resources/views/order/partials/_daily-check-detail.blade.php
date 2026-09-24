<div class="pds-check-detail">
    <div class="pds-check-detail__header">
        <div class="pds-check-detail__header-copy">
            <div class="pds-check-detail__eyebrow">{{ strtoupper($invoice->party_type) }}</div>
            <h5 class="pds-check-detail__title">{{ __('message.check_detail') }}</h5>
            <div class="pds-check-detail__meta">
                <strong>{{ $partyName }}</strong>
                <span>{{ __('message.invoice_no') }} {{ $invoice->invoice_no }}</span>
                <span>{{ $invoice->received_date?->format('d-m-Y') }}</span>
            </div>
        </div>
        <button type="button" class="pds-check-detail__close" data-dismiss="modal" aria-label="Close">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <div class="pds-check-detail__body">
        <div class="table-responsive">
            <table class="table table-sm pds-check-detail-table mb-0">
                <thead>
                    <tr>
                        <th>{{ __('message.no') }}</th>
                        <th>{{ __('message.date') }}</th>
                        <th>{{ __('message.status') }}</th>
                        <th>{{ __('message.os_name') }}</th>
                        <th>{{ __('message.customer') }}</th>
                        <th>{{ __('message.phone') }}</th>
                        <th>{{ __('message.address') }}</th>
                        <th class="text-right">{{ __('message.advance_paid') }}</th>
                        <th class="text-right">{{ __('message.item_value') }}</th>
                        <th class="text-right">{{ __('message.deli_amount') }}</th>
                        <th class="text-right">{{ __('message.cust_get') }}</th>
                        <th class="text-right">{{ __('message.cust_paid') }}</th>
                        <th class="text-right">{{ __('message.os_amount') }}</th>
                        <th class="text-right">{{ __('message.office') }}</th>
                        <th class="text-right">{{ __('message.gate') }}</th>
                        <th class="text-right">{{ __('message.os_to_pay') }}</th>
                        <th class="text-center">{{ __('message.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $sumAdvance = 0;
                        $sumItemValue = 0;
                        $sumDeli = 0;
                        $sumCustGet = 0;
                        $sumCustPaid = 0;
                        $sumOsAmount = 0;
                        $sumOffice = 0;
                        $sumGate = 0;
                        $sumOsToPay = 0;
                        $canEdit = $canEdit ?? false;
                        $statusService = app(\App\Services\DailyCheckListService::class);
                    @endphp
                    @forelse($items as $index => $item)
                        @php
                            $osToPay = $item->displayOsToPay();
                            $osName = function_exists('resolveDispatchOsName')
                                ? resolveDispatchOsName($item->order)
                                : (optional($item->order?->client)->name ?: '-');
                            $custGet = (float) ($item->cust_get ?? 0);
                            // Production rider check detail treats Cust Paid as collected = Cust Get.
                            $custPaid = $custGet;
                            $osAmount = 0.0;
                            $officeAmount = 0.0;
                            $gateAmount = (float) ($item->gate_amount ?? 0);
                            $sumAdvance += (float) $item->advance_paid;
                            $sumItemValue += (float) $item->item_value;
                            $sumDeli += (float) $item->deli_amount;
                            $sumCustGet += $custGet;
                            $sumCustPaid += $custPaid;
                            $sumOsAmount += $osAmount;
                            $sumOffice += $officeAmount;
                            $sumGate += $gateAmount;
                            $sumOsToPay += $osToPay;
                            $orderPhotoUrl = mediaAbsoluteUrl($item->photoMedia);
                            $custPhotoUrl = mediaAbsoluteUrl($item->custPhotoMedia);
                            $custSignUrl = mediaAbsoluteUrl($item->custSignMedia);
                            $uploadUrl = route('order.daily-checklist.item-action-media', $item->id);
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $item->received_date ? $item->received_date->format('d-m-Y') : '-' }}</td>
                            <td class="pds-check-detail-status">{{ $statusService->itemStatusLabel($item) }}</td>
                            <td>{{ $osName }}</td>
                            <td>
                                {{ $item->customer_name ?: '-' }}
                                @if($item->isKyoShinGiven())
                                    <span class="pds-kyo-shin-row-badge">{{ __('message.kyo_shin_title') }}</span>
                                @endif
                            </td>
                            <td>{{ $item->customer_phone ?: '-' }}</td>
                            <td class="pds-check-detail-address" title="{{ $item->customer_address }}">{{ $item->customer_address ?: '-' }}</td>
                            <td class="text-right">{{ number_format((float) $item->advance_paid) }}</td>
                            <td class="text-right">{{ number_format((float) $item->item_value) }}</td>
                            <td class="text-right">
                                {!! formatDispatchDeliAmountHtml($item) !!}
                            </td>
                            <td class="text-right">{{ number_format($custGet) }}</td>
                            <td class="text-right">{{ number_format($custPaid) }}</td>
                            <td class="text-right">{{ number_format($osAmount) }}</td>
                            <td class="text-right">{{ number_format($officeAmount) }}</td>
                            <td class="text-right">{{ number_format($gateAmount) }}</td>
                            <td class="text-right {{ $osToPay < 0 ? 'pds-os-to-pay-negative' : '' }}">{{ number_format($osToPay) }}</td>
                            <td class="text-center pds-check-detail-actions">
                                <div class="pds-check-detail-actions__grid">
                                    <button
                                        type="button"
                                        class="pds-check-detail-action-btn pds-check-detail-action-btn--photo js-daily-check-item-media {{ $orderPhotoUrl ? 'has-media' : '' }}"
                                        title="{{ __('message.check_order_photo') }}"
                                        data-type="order_photo"
                                        data-title="{{ __('message.check_order_photo') }}"
                                        data-url="{{ $uploadUrl }}"
                                        data-photo="{{ $orderPhotoUrl }}"
                                        data-can-edit="{{ $canEdit ? 1 : 0 }}"
                                    >
                                        <i class="fas fa-image" aria-hidden="true"></i>
                                    </button>
                                    <button
                                        type="button"
                                        class="pds-check-detail-action-btn pds-check-detail-action-btn--camera js-daily-check-item-media {{ $custPhotoUrl ? 'has-media' : '' }}"
                                        title="{{ __('message.check_cust_photo') }}"
                                        data-type="cust_photo"
                                        data-title="{{ __('message.check_cust_photo') }}"
                                        data-url="{{ $uploadUrl }}"
                                        data-photo="{{ $custPhotoUrl }}"
                                        data-can-edit="{{ $canEdit ? 1 : 0 }}"
                                    >
                                        <i class="fas fa-camera" aria-hidden="true"></i>
                                    </button>
                                    <button
                                        type="button"
                                        class="pds-check-detail-action-btn pds-check-detail-action-btn--sign js-daily-check-item-media {{ $custSignUrl ? 'has-media' : '' }}"
                                        title="{{ __('message.check_cust_sign') }}"
                                        data-type="cust_sign"
                                        data-title="{{ __('message.check_cust_sign') }}"
                                        data-url="{{ $uploadUrl }}"
                                        data-photo="{{ $custSignUrl }}"
                                        data-can-edit="{{ $canEdit ? 1 : 0 }}"
                                    >
                                        <i class="fas fa-signature" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="17" class="text-center text-muted py-4">{{ __('message.no_record_found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
                @if($items->isNotEmpty())
                    <tfoot>
                        <tr>
                            <td colspan="7" class="text-right font-weight-bold">{{ __('message.total') }}</td>
                            <td class="text-right font-weight-bold">{{ number_format($sumAdvance) }}</td>
                            <td class="text-right font-weight-bold">{{ number_format($sumItemValue) }}</td>
                            <td class="text-right font-weight-bold">{{ number_format($sumDeli) }}</td>
                            <td class="text-right font-weight-bold">{{ number_format($sumCustGet) }}</td>
                            <td class="text-right font-weight-bold">{{ number_format($sumCustPaid) }}</td>
                            <td class="text-right font-weight-bold">{{ number_format($sumOsAmount) }}</td>
                            <td class="text-right font-weight-bold">{{ number_format($sumOffice) }}</td>
                            <td class="text-right font-weight-bold">{{ number_format($sumGate) }}</td>
                            <td class="text-right font-weight-bold {{ $sumOsToPay < 0 ? 'pds-os-to-pay-negative' : '' }}">{{ number_format($sumOsToPay) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
