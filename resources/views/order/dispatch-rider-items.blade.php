<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter pds-dispatch-to-assign-page pds-dispatch-rider-items-page">
        <div class="pds-dispatch-to-assign-screen pds-rider-screen">
            <div class="pds-rider-hero pds-rider-hero--details">
                <div class="pds-rider-hero__copy">
                    <div class="pds-rider-hero__eyebrow">
                        <i class="fas fa-box-open" aria-hidden="true"></i>
                        <span>{{ __('message.rider_list') }}</span>
                    </div>
                    <h4 class="pds-rider-hero__title">{{ $pageTitle }}</h4>
                    <div class="pds-rider-meta-row">
                        <span class="pds-rider-meta-pill">
                            <i class="fas fa-user" aria-hidden="true"></i>
                            {{ $rider->name }}
                        </span>
                        <span class="pds-rider-meta-pill pds-rider-meta-pill--status">{{ $statusLabel }}</span>
                        <span class="pds-rider-meta-pill pds-rider-meta-pill--count">
                            {{ __('message.item_count') }}
                            <strong>{{ $items->count() }}</strong>
                        </span>
                    </div>
                </div>
                <a
                    href="{{ route('order.dispatch.rider-list', array_filter(['from_date' => $filterFromDate, 'to_date' => $filterToDate, 'branch_id' => $branchFilter ?? null])) }}"
                    class="pds-rider-close-btn"
                    title="{{ __('message.close') }}"
                >
                    <i class="fas fa-times"></i>
                </a>
            </div>

            <form method="GET" action="{{ route('order.dispatch.rider-items', ['riderId' => $rider->id]) }}" class="pds-rider-toolbar" id="riderItemsFilterForm">
                <input type="hidden" name="branch_id" value="{{ $branchFilter ?? '' }}">
                <div class="pds-rider-toolbar__fields">
                    <div class="pds-dispatch-field pds-dispatch-field-sm">
                        <label for="rider_items_from_date">{{ __('message.from') }}</label>
                        <input type="text" name="from_date" id="rider_items_from_date" class="pds-dispatch-input dispatch-datepicker" value="{{ $filterFromDate }}" autocomplete="off">
                    </div>
                    <div class="pds-dispatch-field pds-dispatch-field-sm">
                        <label for="rider_items_to_date">{{ __('message.to') }}</label>
                        <input type="text" name="to_date" id="rider_items_to_date" class="pds-dispatch-input dispatch-datepicker" value="{{ $filterToDate }}" autocomplete="off">
                    </div>
                    <div class="pds-dispatch-field pds-dispatch-field-sm pds-rider-toolbar__grow">
                        <label for="rider_items_search">{{ __('message.search') }}</label>
                        <input type="text" name="search" id="rider_items_search" class="pds-dispatch-input" value="{{ $search }}" placeholder="{{ __('message.search') }}" autocomplete="off">
                    </div>
                    <input type="hidden" name="status" value="{{ $status }}">
                </div>
                <div class="pds-rider-toolbar__aside">
                    <button type="submit" class="pds-rider-check-btn">
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <span>{{ __('message.check') }}</span>
                    </button>
                </div>
            </form>

            @if($canBulkUpdate)
                <div class="pds-rider-toolbar pds-rider-toolbar--bulk" id="riderItemsBulkBar">
                    <div class="pds-rider-toolbar__fields">
                        <div class="pds-dispatch-rider-items-total-wrap">
                            <span class="pds-dispatch-rider-items-total-label">{{ __('message.total') }}</span>
                            <div class="pds-dispatch-rider-items-total" id="riderItemsSelectedTotal">0</div>
                        </div>
                    </div>
                    <div class="pds-rider-toolbar__aside">
                        <div class="pds-rider-bulk-actions">
                            <div class="pds-dispatch-field pds-dispatch-field-sm">
                                <label for="riderItemsBulkAction">{{ __('message.update_to') }}</label>
                                <select id="riderItemsBulkAction" class="pds-dispatch-input pds-dispatch-select">
                                    @if(count($bulkActions) !== 1)
                                        <option value="">—</option>
                                    @endif
                                    @foreach($bulkActions as $actionKey => $actionLabel)
                                        <option value="{{ $actionKey }}" @selected(count($bulkActions) === 1)>{{ $actionLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="button" class="pds-rider-check-btn" id="riderItemsBulkUpdate">
                                <i class="fas fa-sync-alt" aria-hidden="true"></i>
                                <span>{{ __('message.update') }}</span>
                            </button>
                            @if(! empty($canReassignRider))
                                <button type="button" class="pds-assign-action-btn pds-assign-action-btn--rider" id="riderItemsAssignRider" disabled>
                                    <span class="pds-assign-action-btn__icon" aria-hidden="true">
                                        <i class="fas fa-motorcycle"></i>
                                    </span>
                                    <span class="pds-assign-action-btn__label">{{ __('message.assigned_rider') }}</span>
                                </button>
                            @endif
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
                        $totalCustGet = 0.0;
                        $totalCustPaid = 0.0;
                        $totalGateAmount = 0.0;
                        $totalOsToPay = 0.0;
                    @endphp
                    <div class="pds-rider-table-shell pds-rider-table-shell--scroll">
                        <table class="table pds-rider-items-table" id="riderItemsTable">
                            <thead>
                                <tr>
                                    <th class="pds-rider-sticky-col pds-rider-sticky-col--no">{{ __('message.no') }}</th>
                                    @if($canBulkUpdate)
                                    <th class="pds-rider-sticky-col pds-rider-sticky-col--check">
                                        <input type="checkbox" id="riderItemsSelectAll" title="{{ __('message.select_all') }}">
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
                                    <th class="text-right">{{ __('message.cust_get') }}</th>
                                    <th class="text-right">{{ __('message.cust_paid') }}</th>
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
                                        $osName = resolveDispatchOsName($order);
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
                                        $custGet = (float) ($item->cust_get ?? 0);
                                        $custPaid = $custGet;
                                        $deliAmount = (float) ($item->deli_amount ?? 0);
                                        $gateAmount = (float) ($item->gate_amount ?? 0);
                                        $osToPayDisplay = $item->displayOsToPay();

                                        $totalOsPaid += $osPaid;
                                        $totalAdvancePaid += $advancePaid;
                                        $totalItemValue += $itemValue;
                                        $totalDeliAmount += $deliAmount;
                                        $totalCustGet += $custGet;
                                        $totalCustPaid += $custPaid;
                                        $totalGateAmount += $gateAmount;
                                        $totalOsToPay += $osToPayDisplay;
                                    @endphp
                                    <tr data-cust-paid="{{ $custPaid }}">
                                        <td class="pds-rider-sticky-col pds-rider-sticky-col--no">{{ $index + 1 }}</td>
                                        @if($canBulkUpdate)
                                        <td class="pds-rider-sticky-col pds-rider-sticky-col--check">
                                            <input type="checkbox" class="pds-rider-item-check" value="{{ $item->id }}">
                                        </td>
                                        @endif
                                        <td>{{ $item->id }}</td>
                                        <td>{{ $orderDate }}</td>
                                        <td>{{ $receivedDate }}</td>
                                        <td><span class="pds-rider-code">{{ $item->code ?? '-' }}</span></td>
                                        <td>{{ $osName }}</td>
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
                                        <td class="text-right pds-rider-money">{{ number_format($custGet) }}</td>
                                        <td class="text-right pds-rider-money">{{ number_format($custPaid) }}</td>
                                        <td class="text-right pds-rider-money js-item-gate-amount">
                                            {{ number_format($gateAmount) }}
                                            @include('order.partials._dispatch-item-delivered-proof', ['item' => $item])
                                        </td>
                                        <td class="text-right pds-rider-money js-item-os-to-pay js-item-os-to-pay-signed {{ $osToPayDisplay < 0 ? 'pds-os-to-pay-negative' : '' }}">
                                            {{ number_format($osToPayDisplay) }}
                                        </td>
                                        <td>
                                            @include('order.partials._pending-remark-history', ['item' => $item, 'photoSize' => 88])
                                        </td>
                                        <td>
                                            @include('order.dispatch-item-action', ['item' => $item])
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="pds-rider-items-total-row">
                                    <td colspan="{{ $canBulkUpdate ? 13 : 12 }}" class="pds-rider-items-total-label-cell">
                                        {{ __('message.total_amount') }}
                                    </td>
                                    <td class="text-right pds-rider-money">{{ $totalOsPaid == 0.0 ? '0' : number_format($totalOsPaid) }}</td>
                                    <td class="text-right pds-rider-money">{{ $totalAdvancePaid == 0.0 ? '0' : number_format($totalAdvancePaid) }}</td>
                                    <td class="text-right pds-rider-money">{{ number_format($totalItemValue) }}</td>
                                    <td class="text-right pds-rider-money">{{ number_format($totalDeliAmount) }}</td>
                                    <td class="text-right pds-rider-money">{{ number_format($totalCustGet) }}</td>
                                    <td class="text-right pds-rider-money">{{ number_format($totalCustPaid) }}</td>
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

    @if($canBulkUpdate)
    <div class="modal fade" id="riderPendingRemarkModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('message.follow_up_status_pending') }} Remark</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="riderPendingRemarkInput">{{ __('message.remark_label') }}</label>
                        <textarea id="riderPendingRemarkInput" class="form-control" rows="3" placeholder="{{ __('message.remark_label') }}"></textarea>
                    </div>
                    <div class="form-group mb-0">
                        <label for="riderPendingPhotoInput">{{ __('message.image') ?? 'Image' }}</label>
                        <input type="file" id="riderPendingPhotoInput" class="form-control-file" accept="image/*">
                        <img src="" alt="" class="pds-delivered-upload__preview is-pending" id="riderPendingUploadPreview" hidden>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('message.cancel') }}</button>
                    <button type="button" class="btn btn-primary" id="riderPendingRemarkConfirm">{{ __('message.update') }}</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="riderDeliveredTypeModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content pds-delivered-modal">
                <div class="pds-delivered-modal__head">
                    <div class="pds-delivered-modal__brand">
                        <span class="pds-delivered-modal__icon" aria-hidden="true">
                            <i class="fas fa-check-circle"></i>
                        </span>
                        <div>
                            <h5 class="pds-delivered-modal__title">{{ __('message.delivered') }}</h5>
                            <p class="pds-delivered-modal__sub">{{ __('message.delivered_type_choose_hint') }}</p>
                        </div>
                    </div>
                    <button type="button" class="pds-delivered-modal__close" data-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="pds-delivered-modal__body">
                    <div class="pds-delivered-modal__section-label">{{ __('message.delivered_type_choose') }}</div>
                    <div class="pds-delivered-modal__choices" role="radiogroup" aria-label="{{ __('message.delivered_type_choose') }}">
                        <label class="pds-delivered-choice is-active" data-delivered-choice="other">
                            <input type="radio" name="rider_delivered_type" value="other" checked>
                            <span class="pds-delivered-choice__icon" aria-hidden="true"><i class="fas fa-map-marker-alt"></i></span>
                            <span class="pds-delivered-choice__copy">
                                <strong>{{ __('message.delivered_type_other') }}</strong>
                                <em>{{ __('message.delivered_type_other_hint') }}</em>
                            </span>
                            <span class="pds-delivered-choice__check" aria-hidden="true"><i class="fas fa-check"></i></span>
                        </label>
                        <label class="pds-delivered-choice" data-delivered-choice="gate">
                            <input type="radio" name="rider_delivered_type" value="gate">
                            <span class="pds-delivered-choice__icon is-gate" aria-hidden="true"><i class="fas fa-archway"></i></span>
                            <span class="pds-delivered-choice__copy">
                                <strong>{{ __('message.delivered_type_gate') }}</strong>
                                <em>{{ __('message.delivered_type_gate_hint') }}</em>
                            </span>
                            <span class="pds-delivered-choice__check" aria-hidden="true"><i class="fas fa-check"></i></span>
                        </label>
                    </div>

                    <div class="pds-delivered-modal__gate" id="riderDeliveredGateAmountWrap" hidden>
                        <label for="riderDeliveredGateAmount">{{ __('message.gate_amount') }}</label>
                        <div class="pds-delivered-modal__gate-input">
                            <span>Ks</span>
                            <input type="number" min="0" step="1" id="riderDeliveredGateAmount" value="0" inputmode="numeric">
                        </div>
                    </div>

                    <div class="pds-delivered-modal__section-label">{{ __('message.delivered_photo') }}</div>
                    <label class="pds-delivered-upload" for="riderDeliveredPhotoInput" id="riderDeliveredUploadLabel">
                        <input type="file" id="riderDeliveredPhotoInput" accept="image/*" hidden>
                        <span class="pds-delivered-upload__icon" id="riderDeliveredUploadIcon" aria-hidden="true"><i class="fas fa-cloud-upload-alt"></i></span>
                        <img src="" alt="" class="pds-delivered-upload__preview" id="riderDeliveredUploadPreview" hidden>
                        <span class="pds-delivered-upload__title" id="riderDeliveredUploadTitle">{{ __('message.delivered_photo_pick') }}</span>
                        <span class="pds-delivered-upload__hint">{{ __('message.delivered_photo_hint') }}</span>
                    </label>
                </div>

                <div class="pds-delivered-modal__foot">
                    <button type="button" class="pds-delivered-modal__btn pds-delivered-modal__btn--ghost" data-dismiss="modal">{{ __('message.cancel') }}</button>
                    <button type="button" class="pds-delivered-modal__btn pds-delivered-modal__btn--primary" id="riderDeliveredTypeConfirm">
                        <i class="fas fa-check" aria-hidden="true"></i>
                        <span>{{ __('message.update') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    @include('order.partials._dispatch-item-message-modal')
    @include('order.partials._dispatch-item-gate-modal')
    @include('order.partials._pending-remark-history-modal')
    @if(! empty($canReassignRider))
        @include('order.partials._rider_assign_modal')
    @endif

    @section('bottom_script')
        <script src="{{ asset('js/dispatch-item-form.js') }}?v=28"></script>
        @include('order.partials._dispatch-item-message-scripts')
        <script>
            $(document).ready(function () {
                // Static table (not DataTables): refresh the page after Admin item edits.
                window.reloadDispatchItemsTable = function () {
                    window.location.reload();
                };

                if (typeof flatpickr !== 'undefined') {
                    flatpickr('.dispatch-datepicker', {
                        dateFormat: 'd-m-Y',
                        allowInput: true,
                    });
                }

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

                var canBulkUpdate = @json($canBulkUpdate);
                var canReassignRider = @json(! empty($canReassignRider));
                var currentRiderId = @json((int) $rider->id);
                var riderBranchId = @json((int) ($rider->branch_id ?? 0));
                var bulkUpdateUrl = @json(route('order.dispatch.rider-items.bulk-update', ['riderId' => $rider->id]));
                var reassignUrl = @json(route('order.dispatch.rider-items.reassign', ['riderId' => $rider->id]));
                var riderSearchUrl = @json(route('ajax-list', ['type' => 'dispatch_deliveryman_search']));
                var riderCache = [];

                function formatAmount(value) {
                    return Number(value || 0).toLocaleString('en-US', {
                        maximumFractionDigits: 0,
                    });
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
                    $('#riderItemsTable tbody .pds-rider-item-check:checked').each(function () {
                        var id = parseInt($(this).val(), 10);
                        if (id > 0) ids.push(id);
                    });
                    return ids;
                }

                function updateSelectedTotal() {
                    if (!canBulkUpdate) {
                        return;
                    }
                    var total = 0;
                    $('#riderItemsTable tbody .pds-rider-item-check:checked').each(function () {
                        total += parseFloat($(this).closest('tr').data('cust-paid')) || 0;
                    });
                    $('#riderItemsSelectedTotal').text(formatAmount(total));
                    $('#riderItemsAssignRider').prop('disabled', selectedItemIds().length === 0);
                }

                $('#riderItemsSelectAll').on('change', function () {
                    var checked = $(this).is(':checked');
                    $('#riderItemsTable tbody .pds-rider-item-check:not(:disabled)').prop('checked', checked);
                    updateSelectedTotal();
                });

                $(document).on('change', '.pds-rider-item-check', function () {
                    var $checks = $('#riderItemsTable tbody .pds-rider-item-check:not(:disabled)');
                    var allChecked = $checks.length > 0 && $checks.filter(':checked').length === $checks.length;
                    $('#riderItemsSelectAll').prop('checked', allChecked);
                    updateSelectedTotal();
                });

                updateSelectedTotal();

                function submitBulkUpdate(toStatus, remark, photoFile, deliveredType, gateAmount, deliveredPhotoFile) {
                    var ids = selectedItemIds();
                    if (!ids.length) {
                        notify(@json(__('message.select_items_to_assign')), 'error');
                        return;
                    }

                    var formData = new FormData();
                    formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
                    formData.append('to_status', toStatus);
                    ids.forEach(function (id, index) {
                        formData.append('item_ids[' + index + ']', id);
                    });
                    if (remark) {
                        formData.append('remark', remark);
                    }
                    if (photoFile) {
                        formData.append('pending_photo', photoFile);
                    }
                    if (deliveredType) {
                        formData.append('delivered_type', deliveredType);
                    }
                    if (gateAmount !== null && gateAmount !== undefined && gateAmount !== '') {
                        formData.append('gate_amount', gateAmount);
                    }
                    if (deliveredPhotoFile) {
                        formData.append('delivered_photo', deliveredPhotoFile);
                    }

                    var $btn = $('#riderItemsBulkUpdate');
                    $btn.prop('disabled', true);
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
                }

                function syncDeliveredGateAmountVisibility() {
                    var type = String($('input[name="rider_delivered_type"]:checked').val() || 'other');
                    $('#riderDeliveredGateAmountWrap').prop('hidden', type !== 'gate');
                    $('.pds-delivered-choice').removeClass('is-active');
                    $('.pds-delivered-choice[data-delivered-choice="' + type + '"]').addClass('is-active');
                }

                function setUploadPreview(imgId, iconId, file) {
                    var img = document.getElementById(imgId);
                    var icon = iconId ? document.getElementById(iconId) : null;
                    if (!img) {
                        return;
                    }
                    if (img.dataset.objectUrl) {
                        URL.revokeObjectURL(img.dataset.objectUrl);
                        delete img.dataset.objectUrl;
                    }
                    if (!file || !file.type || file.type.indexOf('image/') !== 0) {
                        img.hidden = true;
                        img.removeAttribute('src');
                        if (icon) {
                            icon.hidden = false;
                        }
                        return;
                    }
                    var url = URL.createObjectURL(file);
                    img.dataset.objectUrl = url;
                    img.src = url;
                    img.hidden = false;
                    if (icon) {
                        icon.hidden = true;
                    }
                }

                function resetDeliveredUploadLabel() {
                    $('#riderDeliveredUploadLabel').removeClass('has-file');
                    $('#riderDeliveredUploadTitle').text(@json(__('message.delivered_photo_pick')));
                    setUploadPreview('riderDeliveredUploadPreview', 'riderDeliveredUploadIcon', null);
                }

                $(document).on('change', 'input[name="rider_delivered_type"]', syncDeliveredGateAmountVisibility);

                $(document).on('change', '#riderDeliveredPhotoInput', function () {
                    var file = this.files && this.files[0] ? this.files[0] : null;
                    if (!file) {
                        resetDeliveredUploadLabel();
                        return;
                    }
                    $('#riderDeliveredUploadLabel').addClass('has-file');
                    $('#riderDeliveredUploadTitle').text(file.name);
                    setUploadPreview('riderDeliveredUploadPreview', 'riderDeliveredUploadIcon', file);
                });

                $(document).on('change', '#riderPendingPhotoInput', function () {
                    var file = this.files && this.files[0] ? this.files[0] : null;
                    setUploadPreview('riderPendingUploadPreview', null, file);
                });

                $(document).on('click', '#riderItemsBulkUpdate', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (!canBulkUpdate) {
                        return;
                    }
                    var action = String($('#riderItemsBulkAction').val() || '').trim();
                    if (!action) {
                        notify(@json(__('message.please_select_status')), 'error');
                        return;
                    }
                    if (!selectedItemIds().length) {
                        notify(@json(__('message.select_items_to_assign')), 'error');
                        return;
                    }

                    if (action === 'pending') {
                        $('#riderPendingRemarkInput').val('');
                        $('#riderPendingPhotoInput').val('');
                        setUploadPreview('riderPendingUploadPreview', null, null);
                        $('#riderPendingRemarkModal').modal('show');
                        return;
                    }

                    if (action === 'completed') {
                        $('input[name="rider_delivered_type"][value="other"]').prop('checked', true);
                        $('#riderDeliveredGateAmount').val('0');
                        $('#riderDeliveredPhotoInput').val('');
                        resetDeliveredUploadLabel();
                        syncDeliveredGateAmountVisibility();
                        $('#riderDeliveredTypeModal').modal('show');
                        return;
                    }

                    submitBulkUpdate(action, null, null, null, null, null);
                });

                $(document).on('click', '#riderPendingRemarkConfirm', function (e) {
                    e.preventDefault();
                    var remark = String($('#riderPendingRemarkInput').val() || '').trim();
                    var photoInput = document.getElementById('riderPendingPhotoInput');
                    var photoFile = photoInput && photoInput.files && photoInput.files[0] ? photoInput.files[0] : null;
                    if (!remark) {
                        notify(@json(__('message.delivery_item_pending_remark_required')), 'error');
                        return;
                    }
                    if (!photoFile) {
                        notify(@json(__('message.please_select_document_image')), 'error');
                        return;
                    }
                    $('#riderPendingRemarkModal').modal('hide');
                    submitBulkUpdate('pending', remark, photoFile, null, null, null);
                });

                $(document).on('click', '#riderDeliveredTypeConfirm', function (e) {
                    e.preventDefault();
                    var deliveredType = String($('input[name="rider_delivered_type"]:checked').val() || '').trim();
                    var photoInput = document.getElementById('riderDeliveredPhotoInput');
                    var photoFile = photoInput && photoInput.files && photoInput.files[0] ? photoInput.files[0] : null;
                    var gateAmount = $('#riderDeliveredGateAmount').val();

                    if (deliveredType !== 'gate' && deliveredType !== 'other') {
                        notify(@json(__('message.delivered_type_required')), 'error');
                        return;
                    }
                    if (!photoFile) {
                        notify(@json(__('message.delivered_photo_required')), 'error');
                        return;
                    }
                    if (deliveredType === 'gate' && (gateAmount === '' || gateAmount === null || Number(gateAmount) < 0)) {
                        notify(@json(__('message.gate_amount_required')), 'error');
                        return;
                    }

                    $('#riderDeliveredTypeModal').modal('hide');
                    submitBulkUpdate(
                        'completed',
                        null,
                        null,
                        deliveredType,
                        deliveredType === 'gate' ? gateAmount : null,
                        photoFile
                    );
                });

                function renderReassignRiderRows(rows) {
                    var $body = $('#riderAssignTableBody').empty();
                    if (!rows.length) {
                        $body.append('<tr class="pds-dispatch-empty-row"><td colspan="4">' + @json(__('message.no_record_found')) + '</td></tr>');
                        return;
                    }
                    rows.forEach(function (row, idx) {
                        $('<tr class="pds-dispatch-table-row"></tr>')
                            .append('<td>' + (idx + 1) + '</td>')
                            .append('<td>' + (row.name || row.text || '') + '</td>')
                            .append('<td>' + (row.branch || row.city || '-') + '</td>')
                            .append('<td>' + (row.phone || '-') + '</td>')
                            .data('rider', row)
                            .appendTo($body);
                    });
                }

                function filterReassignRiders(term) {
                    var query = String(term || '').toLowerCase().trim();
                    if (!query) {
                        return riderCache;
                    }
                    return riderCache.filter(function (row) {
                        return (row.name || row.text || '').toLowerCase().indexOf(query) !== -1
                            || String(row.phone || '').toLowerCase().indexOf(query) !== -1
                            || String(row.city || '').toLowerCase().indexOf(query) !== -1
                            || String(row.branch || '').toLowerCase().indexOf(query) !== -1;
                    });
                }

                function loadReassignRiders(callback) {
                    var params = {};
                    if (riderBranchId > 0) {
                        params.branch_id = riderBranchId;
                    }
                    $.get(riderSearchUrl, params, function (res) {
                        riderCache = (res.results || []).filter(function (row) {
                            return parseInt(row.id, 10) !== currentRiderId;
                        });
                        if (typeof callback === 'function') {
                            callback(riderCache);
                        }
                    });
                }

                function submitReassignRider(itemIds, riderId) {
                    var $btn = $('#riderItemsAssignRider');
                    $btn.prop('disabled', true);
                    $.ajax({
                        url: reassignUrl,
                        type: 'POST',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content'),
                            item_ids: itemIds,
                            delivery_man_id: riderId,
                        },
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        success: function (res) {
                            $('#riderAssignModal').modal('hide');
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
                            notify(msg, 'error');
                            $btn.prop('disabled', selectedItemIds().length === 0);
                        },
                    });
                }

                if (canReassignRider) {
                    $('#riderItemsAssignRider').on('click', function () {
                        var ids = selectedItemIds();
                        if (!ids.length) {
                            notify(@json(__('message.select_items_to_assign')), 'error');
                            return;
                        }
                        $('#rider_modal_search').val('');
                        loadReassignRiders(function (rows) {
                            renderReassignRiderRows(rows);
                        });
                        $('#riderAssignModal').modal('show');
                    });

                    $('#rider_modal_search').on('input', function () {
                        renderReassignRiderRows(filterReassignRiders($(this).val()));
                    });

                    $(document).on('click', '#riderAssignTableBody .pds-dispatch-table-row', function () {
                        var rider = $(this).data('rider');
                        var ids = selectedItemIds();
                        if (!rider || !rider.id || !ids.length) {
                            return;
                        }
                        submitReassignRider(ids, rider.id);
                    });
                }
            });
        </script>
    @endsection
</x-master-layout>
