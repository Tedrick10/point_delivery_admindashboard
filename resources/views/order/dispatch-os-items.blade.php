<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter pds-dispatch-to-assign-page pds-dispatch-os-items-page">
        <div class="pds-dispatch-to-assign-screen pds-rider-screen">
            <div class="pds-rider-hero pds-rider-hero--details">
                <div class="pds-rider-hero__copy">
                    <div class="pds-rider-hero__eyebrow">
                        <i class="fas fa-box-open" aria-hidden="true"></i>
                        <span>{{ __('message.os_list') }}</span>
                    </div>
                    <h4 class="pds-rider-hero__title">{{ $pageTitle }}</h4>
                    <div class="pds-rider-meta-row">
                        <span class="pds-rider-meta-pill">
                            <i class="fas fa-store" aria-hidden="true"></i>
                            {{ $osName }}
                        </span>
                        <span class="pds-rider-meta-pill pds-rider-meta-pill--status">{{ $statusLabel }}</span>
                        <span class="pds-rider-meta-pill pds-rider-meta-pill--count">
                            {{ __('message.item_count') }}
                            <strong>{{ $items->count() }}</strong>
                        </span>
                    </div>
                </div>
                <a
                    href="{{ route('order.dispatch.os-list', ['from_date' => $filterFromDate, 'to_date' => $filterToDate]) }}"
                    class="pds-rider-close-btn"
                    title="{{ __('message.close') }}"
                >
                    <i class="fas fa-times"></i>
                </a>
            </div>

            <form method="GET" action="{{ route('order.dispatch.os-items', ['osId' => $osId]) }}" class="pds-rider-toolbar" id="osItemsFilterForm">
                <div class="pds-rider-toolbar__fields">
                    <div class="pds-dispatch-field pds-dispatch-field-sm">
                        <label for="os_items_from_date">{{ __('message.from') }}</label>
                        <input type="text" name="from_date" id="os_items_from_date" class="pds-dispatch-input dispatch-datepicker" value="{{ $filterFromDate }}" autocomplete="off">
                    </div>
                    <div class="pds-dispatch-field pds-dispatch-field-sm">
                        <label for="os_items_to_date">{{ __('message.to') }}</label>
                        <input type="text" name="to_date" id="os_items_to_date" class="pds-dispatch-input dispatch-datepicker" value="{{ $filterToDate }}" autocomplete="off">
                    </div>
                    <div class="pds-dispatch-field pds-dispatch-field-sm pds-rider-toolbar__grow">
                        <label for="os_items_search">{{ __('message.search') }}</label>
                        <input type="text" name="search" id="os_items_search" class="pds-dispatch-input" value="{{ $search }}" placeholder="{{ __('message.search') }}" autocomplete="off">
                    </div>
                    <input type="hidden" name="status" value="{{ $status }}">
                </div>
                <div class="pds-rider-toolbar__aside">
                    @if($items->isNotEmpty())
                        <button type="button" class="pds-rider-eye-btn" id="osSlipOpenBtn" title="{{ __('message.show_slip_completed') }}">
                            <i class="fas fa-eye" aria-hidden="true"></i>
                        </button>
                    @endif
                    <button type="submit" class="pds-rider-check-btn">
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <span>{{ __('message.check') }}</span>
                    </button>
                </div>
            </form>

            @if($canBulkUpdate)
                <div class="pds-rider-toolbar pds-rider-toolbar--bulk" id="osItemsBulkBar">
                    <div class="pds-rider-toolbar__fields">
                        <div class="pds-dispatch-rider-items-total-wrap">
                            <span class="pds-dispatch-rider-items-total-label">{{ __('message.total') }}</span>
                            <div class="pds-dispatch-rider-items-total" id="osItemsSelectedTotal">0</div>
                        </div>
                    </div>
                    <div class="pds-rider-toolbar__aside">
                        <div class="pds-rider-bulk-actions">
                            <div class="pds-dispatch-field pds-dispatch-field-sm">
                                <label for="osItemsBulkAction">{{ __('message.update_to') }}</label>
                                <select id="osItemsBulkAction" class="pds-dispatch-input pds-dispatch-select">
                                    @foreach($bulkActions as $actionKey => $actionLabel)
                                        <option value="{{ $actionKey }}" selected>{{ $actionLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="button" class="pds-rider-check-btn" id="osItemsBulkUpdate">
                                <i class="fas fa-sync-alt" aria-hidden="true"></i>
                                <span>{{ __('message.update') }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            <div class="pds-rider-body">
                @if($items->isEmpty())
                    <div class="pds-rider-empty">
                        <div class="pds-rider-empty__icon"><i class="fas fa-inbox"></i></div>
                        <p>{{ __('message.no_record_found') }}</p>
                    </div>
                @else
                    @php
                        $totalOsPaid = 0.0;
                        $totalAdvancePaid = 0.0;
                        $totalItemValue = 0.0;
                        $totalDeliAmount = 0.0;
                        $totalGateAmount = 0.0;
                        $totalOsToPay = 0.0;
                    @endphp
                    <div class="pds-rider-table-shell pds-rider-table-shell--scroll">
                        <table class="table pds-rider-items-table" id="osItemsTable">
                            <thead>
                                <tr>
                                    <th class="pds-rider-sticky-col pds-rider-sticky-col--no">{{ __('message.no') }}</th>
                                    @if($canBulkUpdate)
                                    <th class="pds-rider-sticky-col pds-rider-sticky-col--check">
                                        <input type="checkbox" id="osItemsSelectAll" title="{{ __('message.select_all') }}">
                                    </th>
                                    @endif
                                    <th>{{ __('message.id') }}</th>
                                    <th>{{ __('message.order') }}</th>
                                    <th>{{ __('message.received_date') }}</th>
                                    <th>{{ __('message.voucher_code') }}</th>
                                    <th>{{ __('message.os_name') }}</th>
                                    <th>{{ __('message.os_phone') }}</th>
                                    <th>{{ __('message.os_address') }}</th>
                                    <th>{{ __('message.customer_name') }}</th>
                                    <th>{{ __('message.phone') }}</th>
                                    <th>{{ __('message.township') }}</th>
                                    <th>{{ __('message.address') }}</th>
                                    <th class="text-right">{{ __('message.os_paid') }}</th>
                                    <th class="text-right">{{ __('message.advance_paid') }}</th>
                                    <th class="text-right">{{ __('message.item_value') }}</th>
                                    <th class="text-right">{{ __('message.deli_amount') }}</th>
                                    <th class="text-right">{{ __('message.gate') }}</th>
                                    <th class="text-right">{{ __('message.os_to_pay') }}</th>
                                    <th>{{ __('message.remark_label') }}</th>
                                    <th>{{ __('message.action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $index => $item)
                                    @php
                                        $order = $item->order;
                                        $osNameRow = resolveDispatchOsName($order);
                                        $osPhone = resolveDispatchOsPhone($order);
                                        $osAddress = resolveDispatchOsAddress($order);
                                        $township = \App\Models\DispatchOrderItem::deliveryCityLabel($item->delivery_city);
                                        if ($township === '-' && $item->township) {
                                            $township = $item->township;
                                        }
                                        $orderDate = $order?->created_at
                                            ? \Carbon\Carbon::parse($order->created_at)->format('d-m-Y')
                                            : '-';
                                        $receivedDate = $item->received_date
                                            ? \Carbon\Carbon::parse($item->received_date)->format('d-m-Y')
                                            : '-';
                                        $osPaid = (float) ($item->os_paid ?? 0);
                                        $advancePaid = (float) ($item->advance_paid ?? 0);
                                        $itemValue = (float) ($item->item_value ?? 0);
                                        $deliAmount = (float) ($item->deli_amount ?? 0);
                                        $gateAmount = (float) ($item->gate_amount ?? 0);
                                        $osToPayDisplay = $item->displayOsToPay();
                                        $osToPaySlip = formatDispatchOsToPaySlip($osToPayDisplay);

                                        $totalOsPaid += $osPaid;
                                        $totalAdvancePaid += $advancePaid;
                                        $totalItemValue += $itemValue;
                                        $totalDeliAmount += $deliAmount;
                                        $totalGateAmount += $gateAmount;
                                        $totalOsToPay += $osToPaySlip['value'];
                                    @endphp
                                    <tr data-os-to-pay="{{ $osToPaySlip['value'] }}">
                                        <td class="pds-rider-sticky-col pds-rider-sticky-col--no">{{ $index + 1 }}</td>
                                        @if($canBulkUpdate)
                                        <td class="pds-rider-sticky-col pds-rider-sticky-col--check">
                                            <input type="checkbox" class="pds-os-item-check" value="{{ $item->id }}">
                                        </td>
                                        @endif
                                        <td>{{ $item->id }}</td>
                                        <td>{{ $orderDate }}</td>
                                        <td>{{ $receivedDate }}</td>
                                        <td><span class="pds-rider-code">{{ $item->code ?? '-' }}</span></td>
                                        <td>{{ $osNameRow }}</td>
                                        <td>{{ $osPhone !== '-' ? $osPhone : '' }}</td>
                                        <td title="{{ $osAddress }}">{{ stringLong($osAddress, 'title', 18) ?: '' }}</td>
                                        <td>{{ $item->customer_name ?: '-' }}</td>
                                        <td>{{ $item->customer_phone ?: '-' }}</td>
                                        <td>{{ $township }}</td>
                                        <td title="{{ $item->customer_address }}">{{ stringLong($item->customer_address ?? '', 'title', 18) ?: '-' }}</td>
                                        <td class="text-right pds-rider-money">{{ $osPaid == 0.0 ? '0' : number_format($osPaid) }}</td>
                                        <td class="text-right pds-rider-money">{{ $advancePaid == 0.0 ? '0' : number_format($advancePaid) }}</td>
                                        <td class="text-right pds-rider-money">{{ number_format($itemValue) }}</td>
                                        <td class="text-right pds-rider-money">{!! formatDispatchDeliAmountHtml($item, $deliAmount) !!}</td>
                                        <td class="text-right pds-rider-money js-item-gate-amount">
                                            {{ number_format($gateAmount) }}
                                            @include('order.partials._dispatch-item-delivered-proof', ['item' => $item])
                                        </td>
                                        <td class="text-right pds-rider-money js-item-os-to-pay js-item-os-to-pay-slip {{ $osToPaySlip['is_receive'] ? 'pds-os-to-pay-negative' : '' }}">
                                            {{ $osToPaySlip['formatted'] }}
                                        </td>
                                        <td title="{{ $item->remark }}">
                                            <div>{{ stringLong($item->remark ?? '', 'title', 16) ?: '-' }}</div>
                                            @php
                                                $pendingPhotoUrl = null;
                                                if ((int) ($item->pending_photo_id ?? 0) > 0) {
                                                    $pendingMedia = $item->pendingPhotoMedia;
                                                    if ($pendingMedia) {
                                                        $pendingPhotoUrl = function_exists('mediaPublicUrl')
                                                            ? (mediaPublicUrl($pendingMedia) ?: mediaAbsoluteUrl($pendingMedia))
                                                            : mediaAbsoluteUrl($pendingMedia);
                                                    }
                                                }
                                            @endphp
                                            @if($pendingPhotoUrl)
                                                <a href="{{ $pendingPhotoUrl }}" target="_blank" rel="noopener" class="d-inline-block mt-1">
                                                    <img src="{{ $pendingPhotoUrl }}" alt="Pending" style="width: 48px; height: 48px; object-fit: cover; border-radius: 8px; border: 1px solid #e5e7eb;">
                                                </a>
                                            @endif
                                        </td>
                                        <td>
                                            @include('order.dispatch-item-action', ['item' => $item])
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="pds-rider-items-total-row">
                                    {{-- Leading: No + [Check] + Id..Address = 13 with checkbox, 12 without --}}
                                    <td colspan="{{ $canBulkUpdate ? 13 : 12 }}" class="pds-rider-items-total-label-cell">
                                        {{ __('message.total_amount') }}
                                    </td>
                                    <td class="text-right pds-rider-money">{{ $totalOsPaid == 0.0 ? '0' : number_format($totalOsPaid) }}</td>
                                    <td class="text-right pds-rider-money">{{ $totalAdvancePaid == 0.0 ? '0' : number_format($totalAdvancePaid) }}</td>
                                    <td class="text-right pds-rider-money">{{ number_format($totalItemValue) }}</td>
                                    <td class="text-right pds-rider-money">{{ number_format($totalDeliAmount) }}</td>
                                    <td class="text-right pds-rider-money">{{ number_format($totalGateAmount) }}</td>
                                    <td class="text-right pds-rider-money {{ $totalOsToPay < 0 ? 'pds-os-to-pay-negative' : '' }}">
                                        {{ number_format($totalOsToPay) }}
                                    </td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if($items->isNotEmpty())
        @include('order.dispatch-os-slip-completed')
    @endif

    @include('order.partials._dispatch-item-message-modal')
    @include('order.partials._dispatch-item-gate-modal')

    @section('bottom_script')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="{{ asset('js/dispatch-item-form.js') }}?v=28"></script>
        @include('order.partials._dispatch-item-message-scripts')
        <script>
            $(document).ready(function () {
                window.reloadDispatchItemsTable = function () {
                    window.location.reload();
                };

                if (typeof flatpickr !== 'undefined') {
                    flatpickr('.dispatch-datepicker', {
                        dateFormat: 'd-m-Y',
                        allowInput: true,
                    });
                }

                function openOsSlip() {
                    $('#osSlipCompletedModal').modal('show');
                }

                $(document).on('click', '#osSlipOpenBtn', function (e) {
                    e.preventDefault();
                    openOsSlip();
                });

                $(document).on('click', '#osSlipScreenshotBtn', function (e) {
                    e.preventDefault();
                    var target = document.getElementById('osSlipCompletedCapture');
                    if (!target || typeof html2canvas !== 'function') {
                        if (window.alert) window.alert(@json(__('message.something_went_wrong')));
                        return;
                    }
                    var $btn = $(this);
                    $btn.prop('disabled', true);
                    html2canvas(target, {
                        backgroundColor: '#ffffff',
                        scale: 2,
                        useCORS: true,
                        allowTaint: true,
                    }).then(function (canvas) {
                        var link = document.createElement('a');
                        link.download = 'slip-completed-{{ $filterToDate ?? 'export' }}.png';
                        link.href = canvas.toDataURL('image/png');
                        link.click();
                    }).catch(function () {
                        if (window.alert) window.alert(@json(__('message.something_went_wrong')));
                    }).finally(function () {
                        $btn.prop('disabled', false);
                    });
                });

                $(document).on('click', '.pds-dispatch-action-edit.loadRemoteModel', function (e) {
                    e.preventDefault();
                });

                $(document).on('click', '[data-dispatch-item-delete]', function (e) {
                    e.preventDefault();
                    var $btn = $(this);
                    var url = $btn.data('delete-url');
                    var title = $btn.data('title') || '';
                    var message = $btn.data('message') || '';
                    if (!url) return;

                    Swal.fire({
                        title: title,
                        text: message,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#f97316',
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: @json(__('message.yes')),
                        cancelButtonText: @json(__('message.no')),
                    }).then(function (result) {
                        if (!result.isConfirmed) return;
                        $.ajax({
                            url: url,
                            type: 'POST',
                            data: {
                                _method: 'DELETE',
                                _token: $('meta[name="csrf-token"]').attr('content'),
                            },
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                            success: function (res) {
                                if (res && res.message) {
                                    SnackBar({ message: res.message, status: 'success' });
                                }
                                window.location.reload();
                            },
                            error: function (xhr) {
                                var msg = (xhr.responseJSON && xhr.responseJSON.message)
                                    ? xhr.responseJSON.message
                                    : @json(__('message.something_went_wrong'));
                                SnackBar({ message: msg, status: 'error' });
                            },
                        });
                    });
                });

                var canBulkUpdate = @json($canBulkUpdate ?? false);
                var bulkUpdateUrl = @json(route('order.dispatch.os-items.bulk-update', ['osId' => $osId]));

                function formatAmount(value) {
                    return Number(value || 0).toLocaleString('en-US', { maximumFractionDigits: 0 });
                }

                function notify(message, status, onDone) {
                    status = status || 'error';
                    var icon = status === 'success' ? 'success' : 'error';
                    if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
                        Swal.fire({
                            icon: icon,
                            title: message,
                            confirmButtonColor: '#f97316',
                            confirmButtonText: 'OK',
                            allowOutsideClick: false,
                        }).then(function () {
                            if (typeof onDone === 'function') onDone();
                        });
                        return;
                    }
                    if (typeof SnackBar === 'function') {
                        SnackBar({ message: message, status: status });
                    }
                    if (typeof onDone === 'function') onDone();
                }

                function selectedItemIds() {
                    var ids = [];
                    $('#osItemsTable tbody .pds-os-item-check:checked').each(function () {
                        var id = parseInt($(this).val(), 10);
                        if (id > 0) ids.push(id);
                    });
                    return ids;
                }

                function updateSelectedTotal() {
                    if (!canBulkUpdate) return;
                    var total = 0;
                    $('#osItemsTable tbody .pds-os-item-check:checked').each(function () {
                        total += parseFloat($(this).closest('tr').data('os-to-pay')) || 0;
                    });
                    $('#osItemsSelectedTotal').text(formatAmount(total));
                }

                $('#osItemsSelectAll').on('change', function () {
                    var checked = $(this).is(':checked');
                    $('#osItemsTable tbody .pds-os-item-check:not(:disabled)').prop('checked', checked);
                    updateSelectedTotal();
                });

                $(document).on('change', '.pds-os-item-check', function () {
                    var $checks = $('#osItemsTable tbody .pds-os-item-check:not(:disabled)');
                    var allChecked = $checks.length > 0 && $checks.filter(':checked').length === $checks.length;
                    $('#osItemsSelectAll').prop('checked', allChecked);
                    updateSelectedTotal();
                });

                updateSelectedTotal();

                $(document).on('click', '#osItemsBulkUpdate', function (e) {
                    e.preventDefault();
                    if (!canBulkUpdate) return;
                    var action = String($('#osItemsBulkAction').val() || '').trim();
                    var ids = selectedItemIds();
                    if (!action) {
                        notify(@json(__('message.please_select_status')), 'error');
                        return;
                    }
                    if (!ids.length) {
                        notify(@json(__('message.select_items_to_assign')), 'error');
                        return;
                    }

                    var $btn = $(this);
                    $btn.prop('disabled', true);
                    var formData = new FormData();
                    formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
                    formData.append('to_status', action);
                    ids.forEach(function (id, index) {
                        formData.append('item_ids[' + index + ']', id);
                    });

                    $.ajax({
                        url: bulkUpdateUrl,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        success: function (res) {
                            if (res && res.message) {
                                notify(res.message, 'success', function () {
                                    window.location.reload();
                                });
                            } else {
                                window.location.reload();
                            }
                        },
                        error: function (xhr) {
                            var msg = (xhr.responseJSON && xhr.responseJSON.message)
                                ? xhr.responseJSON.message
                                : @json(__('message.something_went_wrong'));
                            if (xhr.responseJSON && xhr.responseJSON.errors) {
                                msg = Object.values(xhr.responseJSON.errors).flat().join('\n');
                            }
                            notify(msg, 'error');
                            $btn.prop('disabled', false);
                        },
                    });
                });
            });
        </script>
    @endsection
</x-master-layout>
