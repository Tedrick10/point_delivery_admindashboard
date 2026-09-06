<x-master-layout :assets="$assets ?? []">
    <style>
        .pds-assign-100-tabs {
            display: flex; flex-wrap: wrap; gap: 8px;
            margin: 0 0 14px; padding: 0 2px;
        }
        .pds-assign-100-tab {
            display: inline-flex; align-items: center; gap: 8px;
            border: 1px solid #e2e8f0; background: #fff; color: #334155;
            border-radius: 999px; padding: 8px 14px; font-weight: 700;
            text-decoration: none; transition: .15s ease;
        }
        .pds-assign-100-tab em {
            font-style: normal; min-width: 22px; height: 22px; padding: 0 6px;
            border-radius: 999px; background: #f1f5f9; color: #64748b;
            display: inline-grid; place-items: center; font-size: 12px;
        }
        .pds-assign-100-tab:hover { border-color: #fdba74; color: #c2410c; text-decoration: none; }
        .pds-assign-100-tab.is-active {
            background: linear-gradient(135deg, #FE6F07, #ff8f3d);
            border-color: transparent; color: #fff;
            box-shadow: 0 8px 18px rgba(254, 111, 7, .24);
        }
        .pds-assign-100-tab.is-active em { background: rgba(255,255,255,.22); color: #fff; }
        .pds-dm-branch-tabs {
            display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px;
        }
        .pds-dm-branch-tab {
            display: inline-flex; align-items: center; gap: 8px;
            border: 1px solid #e2e8f0; background: #fff; color: #334155;
            border-radius: 999px; padding: 8px 14px; font-weight: 700;
            text-decoration: none;
        }
        .pds-dm-branch-tab:hover { border-color: #fdba74; color: #c2410c; text-decoration: none; }
        .pds-dm-branch-tab.is-active {
            background: linear-gradient(135deg, #FE6F07, #ff8f3d);
            border-color: transparent; color: #fff;
        }
    </style>
    <div class="container-fluid pds-page-wrap pds-motion-enter pds-dispatch-to-assign-page pds-dispatch-assign-100-page">
        <div class="pds-dispatch-to-assign-screen">
            <div class="pds-dispatch-to-assign-topbar">
                <div class="pds-dispatch-to-assign-topbar-copy">
                    <h4 class="pds-dispatch-to-assign-heading">{{ $pageTitle ?? __('message.assign_100') }}</h4>
                    <p class="pds-dispatch-to-assign-subtitle">
                        {{ __('message.item_count') }} = <strong id="assign100ItemCount">{{ $items->count() }}</strong>
                    </p>
                </div>
                <div class="pds-dispatch-to-assign-topbar-actions">
                    <button type="button" class="pds-assign-action-btn pds-assign-action-btn--rider" id="assignRiderBtn" disabled>
                        <span class="pds-assign-action-btn__icon" aria-hidden="true">
                            <i class="fas fa-motorcycle"></i>
                        </span>
                        <span class="pds-assign-action-btn__label">{{ __('message.assign_rider') }}</span>
                    </button>
                    <button type="button" class="pds-dispatch-items-icon-btn" onclick="window.print()" title="{{ __('message.print') }}">
                        <i class="fas fa-print"></i>
                    </button>
                </div>
            </div>

            @php
                $destinationBranches = $destinationBranches ?? collect();
                $activeToBranchId = (int) ($activeToBranchId ?? 0);
                $tabCounts = $tabCounts ?? collect();
            @endphp
            @if($destinationBranches->isNotEmpty())
                <div class="pds-assign-100-tabs">
                    @foreach($destinationBranches as $branchTab)
                        @php $count = (int) ($tabCounts[$branchTab->id] ?? 0); @endphp
                        <a href="{{ route('order.dispatch.assign-100', ['to_branch_id' => $branchTab->id]) }}"
                           class="pds-assign-100-tab{{ $activeToBranchId === (int) $branchTab->id ? ' is-active' : '' }}">
                            <span>{{ $branchTab->name }}</span>
                            <em>{{ $count }}</em>
                        </a>
                    @endforeach
                </div>
            @endif

            <div class="pds-dispatch-to-assign-filter">
                <div class="pds-dispatch-to-assign-filter-grid pds-dispatch-assign-100-filter-grid">
                    <div class="pds-dispatch-field pds-dispatch-field-sm">
                        <label for="assign_100_status">{{ __('message.status') }}</label>
                        <select id="assign_100_status" class="pds-dispatch-input pds-dispatch-select" disabled>
                            <option value="assigned" selected>ASSIGNED</option>
                        </select>
                    </div>
                    <div class="pds-dispatch-to-assign-os-filter">
                        <div class="pds-dispatch-field pds-dispatch-field-sm">
                            <label for="assign_100_os_name">{{ __('message.os_name') }}</label>
                            <input type="text" id="assign_100_os_name" class="pds-dispatch-input" placeholder="{{ __('message.os_name') }}" autocomplete="off">
                        </div>
                        <button type="button" class="pds-dispatch-to-assign-os-search-btn" id="openAssign100OsSearch" title="{{ __('message.to_find_os_name') }}">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <div class="pds-dispatch-field pds-dispatch-field-sm">
                        <label for="assign_100_customer_search">{{ __('message.customer_name') }} / {{ __('message.phone') }}</label>
                        <input type="text" id="assign_100_customer_search" class="pds-dispatch-input" placeholder="{{ __('message.customer_name') }} / {{ __('message.phone') }}" autocomplete="off">
                    </div>
                </div>
            </div>

            <div class="pds-dispatch-to-assign-body">
                @if($items->isEmpty())
                    <div class="pds-dispatch-to-assign-empty" id="assign100EmptyState">
                        <i class="fas fa-inbox"></i>
                        <p>{{ __('message.no_record_found') }}</p>
                    </div>
                @else
                    <div class="pds-dispatch-to-assign-empty d-none" id="assign100FilterEmptyState">
                        <i class="fas fa-search"></i>
                        <p>{{ __('message.no_record_found') }}</p>
                    </div>
                    <div class="pds-dispatch-to-assign-table-shell pds-table-shell" id="assign100TableShell">
                        <table class="table w-100 pds-dispatch-to-assign-table" id="assign100Table">
                            <thead>
                                <tr>
                                    <th>{{ __('message.no') }}</th>
                                    <th>
                                        <input type="checkbox" id="assign100SelectAll" title="{{ __('message.select_all') }}">
                                    </th>
                                    <th>{{ __('message.order') }}</th>
                                    <th>{{ __('message.received_date') }}</th>
                                    <th>{{ __('message.voucher_code') }}</th>
                                    <th>{{ __('message.status') }}</th>
                                    <th>{{ __('message.from_to') }}</th>
                                    <th>{{ __('message.os_name') }}</th>
                                    <th>{{ __('message.os_address') }}</th>
                                    <th>{{ __('message.pickup') }}</th>
                                    <th>{{ __('message.customer_name') }}</th>
                                    <th>{{ __('message.phone') }}</th>
                                    <th>{{ __('message.address') }}</th>
                                    <th>{{ __('message.township') }}</th>
                                    <th>{{ __('message.delivery_man') }}</th>
                                    <th>{{ __('message.cust_paid') }}</th>
                                    <th>{{ __('message.item_value') }}</th>
                                    <th>{{ __('message.deli_amount') }}</th>
                                    <th>{{ __('message.remark_label') }}</th>
                                    <th>{{ __('message.action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $index => $item)
                                    @php
                                        $workflow = app(\App\Services\DispatchOrderWorkflowService::class);
                                        $order = $item->order;
                                        $pickup = resolveDispatchPickupPoint($order);
                                        $osName = resolveDispatchOsName($order);
                                        $osAddress = resolveDispatchOsAddress($order);
                                        $pickupRider = optional($order?->delivery_man)->name ?? '-';
                                        $deliveryRider = optional($item->deliveryMan)->name ?? '-';
                                        $fromTo = trim((optional($item->fromBranch)->name ?? '-') . ' - ' . (optional($item->toBranch)->name ?? '-'));
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
                                    @endphp
                                    <tr
                                        data-os-name="{{ $osName !== '-' ? $osName : '' }}"
                                        data-customer-name="{{ $item->customer_name ?: '' }}"
                                        data-customer-phone="{{ $item->customer_phone ?: '' }}"
                                        data-item-id="{{ $item->id }}"
                                        data-to-branch-id="{{ (int) ($item->to_branch_id ?? 0) }}"
                                    >
                                        <td class="assign-100-row-no">{{ $index + 1 }}</td>
                                        <td>
                                            <input type="checkbox" class="pds-dispatch-item-check" value="{{ $item->id }}">
                                        </td>
                                        <td>{{ $orderDate }}</td>
                                        <td>{{ $receivedDate }}</td>
                                        <td>{{ $item->code ?? '-' }}</td>
                                        <td>
                                            <span class="pds-dispatch-status {{ $workflow->assign100ItemStatusClass($item) }}">{{ $workflow->assign100ItemStatusLabel($item) }}</span>
                                        </td>
                                        <td>{{ $fromTo }}</td>
                                        <td>{{ $osName }}</td>
                                        <td title="{{ $osAddress }}">{{ stringLong($osAddress, 'title', 18) ?: '-' }}</td>
                                        <td>{{ $pickupRider }}</td>
                                        <td>{{ $item->customer_name ?: '-' }}</td>
                                        <td>{{ $item->customer_phone ?: '-' }}</td>
                                        <td title="{{ $item->customer_address }}">{{ stringLong($item->customer_address ?? '', 'title', 18) ?: '-' }}</td>
                                        <td>{{ $township }}</td>
                                        <td>{{ $deliveryRider !== '-' ? $deliveryRider : '-' }}</td>
                                        <td>{{ (float) $item->advance_paid == 0.0 ? '' : number_format((float) $item->advance_paid) }}</td>
                                        <td>{{ number_format((float) $item->item_value) }}</td>
                                        <td class="text-right">{!! formatDispatchDeliAmountHtml($item) !!}</td>
                                        <td title="{{ $item->remark }}">{{ stringLong($item->remark ?? '', 'title', 16) ?: '-' }}</td>
                                        <td>
                                            @include('order.dispatch-item-action', ['item' => $item, 'hideGate' => true])
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @include('order.partials._os_search_modal')
    @include('order.partials._rider_assign_modal')
    @include('order.partials._dispatch-item-message-modal')

    @section('bottom_script')
        <script src="{{ asset('js/dispatch-os-fields.js') }}?v=5"></script>
        @include('order.partials._dispatch-item-message-scripts')
        <script>
            $(document).ready(function () {
                var osSearchRoute = "{{ route('ajax-list', ['type' => 'os_dispatch_search']) }}";
                var riderRoute = "{{ route('ajax-list', ['type' => 'dispatch_deliveryman_search']) }}";
                var activeToBranchId = {{ (int) ($activeToBranchId ?? 0) }};
                var osInputSelector = '#assign_100_os_name';
                var riderCache = [];

                window.reloadDispatchItemsTable = function () {
                    window.location.reload();
                };

                function renderOsRows(rows) {
                    var $body = $('#osSearchTableBody').empty();
                    if (!rows.length) {
                        $body.append('<tr class="pds-dispatch-empty-row"><td colspan="3">{{ __('message.no_record_found') }}</td></tr>');
                        return;
                    }
                    rows.forEach(function (row, idx) {
                        $('<tr class="pds-dispatch-table-row"></tr>')
                            .append('<td>' + (idx + 1) + '</td><td>' + (row.name || row.text || '') + '</td><td>' + (row.phone || '') + '</td>')
                            .data('item', row)
                            .appendTo($body);
                    });
                }

                function renderRiderRows(rows) {
                    var $body = $('#riderAssignTableBody').empty();
                    if (!rows.length) {
                        $body.append('<tr class="pds-dispatch-empty-row"><td colspan="4">{{ __('message.no_record_found') }}</td></tr>');
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

                function filterRiders(term) {
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

                function loadRiders(callback) {
                    var params = {};
                    if (activeToBranchId > 0) {
                        params.branch_id = activeToBranchId;
                    }
                    $.get(riderRoute, params, function (res) {
                        riderCache = res.results || [];
                        if (typeof callback === 'function') {
                            callback(riderCache);
                        }
                    });
                }

                function openOsSearchModal() {
                    $('#os_modal_search').val('');
                    if (typeof window.refreshOsClientCache === 'function') {
                        window.refreshOsClientCache(function () {
                            renderOsRows(window.getOsClientList ? window.getOsClientList() : []);
                        });
                    } else {
                        $.get(osSearchRoute, { list_all: 1 }, function (res) {
                            renderOsRows(res.results || []);
                        });
                    }
                    $('#osSearchModal').modal('show');
                }

                function openRiderAssignModal() {
                    $('#rider_modal_search').val('');
                    loadRiders(function (rows) {
                        renderRiderRows(rows);
                    });
                    $('#riderAssignModal').modal('show');
                }

                function normalizeFilterValue(value) {
                    return String(value || '').toLowerCase().trim();
                }

                function applyAssign100Filters() {
                    var osTerm = normalizeFilterValue($(osInputSelector).val());
                    var customerTerm = normalizeFilterValue($('#assign_100_customer_search').val());
                    var visibleCount = 0;

                    $('#assign100Table tbody tr').each(function () {
                        var $row = $(this);
                        var rowOsName = normalizeFilterValue($row.data('os-name'));
                        var rowCustomerName = normalizeFilterValue($row.data('customer-name'));
                        var rowCustomerPhone = normalizeFilterValue($row.data('customer-phone'));
                        var isMatch = (!osTerm || rowOsName.indexOf(osTerm) !== -1)
                            && (!customerTerm || rowCustomerName.indexOf(customerTerm) !== -1 || rowCustomerPhone.indexOf(customerTerm) !== -1);

                        $row.toggleClass('d-none', !isMatch);
                        if (isMatch) {
                            visibleCount += 1;
                            $row.find('.assign-100-row-no').text(visibleCount);
                        }
                    });

                    $('#assign100ItemCount').text(visibleCount);
                    $('#assign100FilterEmptyState').toggleClass('d-none', visibleCount > 0);
                    $('#assign100TableShell').toggleClass('d-none', visibleCount === 0);
                    updateAssignRiderButtonState();
                }

                function getVisibleCheckedItemIds() {
                    var ids = [];
                    $('#assign100Table tbody tr:not(.d-none) .pds-dispatch-item-check:checked').each(function () {
                        ids.push(parseInt($(this).val(), 10));
                    });
                    return ids;
                }

                function updateAssignRiderButtonState() {
                    var count = getVisibleCheckedItemIds().length;
                    $('#assignRiderBtn').prop('disabled', count === 0);
                }

                function submitAssignRider(itemIds, riderId) {
                    if (!itemIds.length || !riderId) {
                        return;
                    }

                    if (itemIds.length > 100) {
                        if (typeof errorMessage === 'function') {
                            errorMessage('{{ __('message.max_assign_100_items') }}');
                        }
                        return;
                    }

                    $('#assignRiderBtn').prop('disabled', true);

                    $.ajax({
                        url: "{{ route('order.dispatch.assign-rider') }}",
                        type: 'POST',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content'),
                            item_ids: itemIds,
                            delivery_man_id: riderId
                        },
                        success: function (res) {
                            $('#riderAssignModal').modal('hide');
                            if (res && res.message && typeof showMessage === 'function') {
                                showMessage(res.message);
                            }
                            if (res && res.redirect) {
                                window.location.href = res.redirect;
                                return;
                            }
                            window.location.reload();
                        },
                        error: function (xhr) {
                            updateAssignRiderButtonState();
                            var msg = (xhr.responseJSON && xhr.responseJSON.message)
                                ? xhr.responseJSON.message
                                : '{{ __('message.something_went_wrong') }}';
                            if (typeof errorMessage === 'function') {
                                errorMessage(msg);
                            }
                        }
                    });
                }

                if (typeof window.initDispatchOsFields === 'function') {
                    window.initDispatchOsFields({ osSearchRoute: osSearchRoute });
                }

                $('#openAssign100OsSearch').on('click', openOsSearchModal);

                var osSearchTimer;
                $('#os_modal_search').on('input', function () {
                    clearTimeout(osSearchTimer);
                    var term = $(this).val();
                    osSearchTimer = setTimeout(function () {
                        if (typeof window.getOsClientList === 'function') {
                            renderOsRows(window.getOsClientList(term));
                            return;
                        }
                        $.get(osSearchRoute, { q: term }, function (res) {
                            renderOsRows(res.results || []);
                        });
                    }, 200);
                });

                var riderSearchTimer;
                $('#rider_modal_search').on('input', function () {
                    clearTimeout(riderSearchTimer);
                    var term = $(this).val();
                    riderSearchTimer = setTimeout(function () {
                        renderRiderRows(filterRiders(term));
                    }, 200);
                });

                $(document).on('click', '#osSearchTableBody .pds-dispatch-table-row', function () {
                    var item = $(this).data('item');
                    if (!item) {
                        return;
                    }
                    var osName = item.name || String(item.text || '').replace(/\s*\([^)]*\)\s*$/, '').trim();
                    $(osInputSelector).val(osName);
                    $('#osSearchModal').modal('hide');
                    applyAssign100Filters();
                });

                $(document).on('click', '#riderAssignTableBody .pds-dispatch-table-row', function () {
                    var rider = $(this).data('rider');
                    var itemIds = getVisibleCheckedItemIds();
                    if (!rider || !rider.id || !itemIds.length) {
                        return;
                    }
                    submitAssignRider(itemIds, rider.id);
                });

                var assign100FilterTimer;
                $(osInputSelector + ', #assign_100_customer_search').on('input', function () {
                    clearTimeout(assign100FilterTimer);
                    assign100FilterTimer = setTimeout(applyAssign100Filters, 150);
                });

                $(document).on('change', '#assign100SelectAll', function () {
                    var isChecked = $(this).is(':checked');
                    $('#assign100Table tbody tr:not(.d-none) .pds-dispatch-item-check').prop('checked', isChecked);
                    updateAssignRiderButtonState();
                });

                $(document).on('change', '.pds-dispatch-item-check', function () {
                    var $visibleChecks = $('#assign100Table tbody tr:not(.d-none) .pds-dispatch-item-check');
                    var allChecked = $visibleChecks.length > 0 && $visibleChecks.filter(':checked').length === $visibleChecks.length;
                    $('#assign100SelectAll').prop('checked', allChecked);
                    updateAssignRiderButtonState();
                });

                $('#assignRiderBtn').on('click', function () {
                    var itemIds = getVisibleCheckedItemIds();
                    if (!itemIds.length) {
                        if (typeof errorMessage === 'function') {
                            errorMessage('{{ __('message.select_items_to_assign') }}');
                        }
                        return;
                    }
                    openRiderAssignModal();
                });

                updateAssignRiderButtonState();

                $(document).on('click', '.pds-dispatch-action-edit.loadRemoteModel', function (e) {
                    e.stopPropagation();
                });

                $(document).on('click', '[data-dispatch-item-delete]', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    var $btn = $(this);
                    var url = $btn.data('delete-url');
                    var title = $btn.data('title') || '{{ __('message.confirmation') }}';
                    var message = $btn.data('message') || '{{ __('message.delete_msg') }}';
                    var storageDark = localStorage.getItem('dark');
                    var theme = (storageDark == 'false') ? 'material' : 'dark';

                    if (!url || typeof $.confirm !== 'function') {
                        return;
                    }

                    $.confirm({
                        title: title,
                        content: message,
                        type: '',
                        theme: theme,
                        buttons: {
                            yes: {
                                action: function () {
                                    $.ajax({
                                        url: url,
                                        type: 'DELETE',
                                        headers: {
                                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                        },
                                        success: function (res) {
                                            if (res && res.message && typeof showMessage === 'function') {
                                                showMessage(res.message);
                                            }
                                            window.location.reload();
                                        },
                                        error: function (xhr) {
                                            var msg = (xhr.responseJSON && xhr.responseJSON.message)
                                                ? xhr.responseJSON.message
                                                : '{{ __('message.something_went_wrong') }}';
                                            if (typeof errorMessage === 'function') {
                                                errorMessage(msg);
                                            }
                                        }
                                    });
                                }
                            },
                            no: {
                                action: function () {}
                            }
                        }
                    });
                });
            });
        </script>
    @endsection
</x-master-layout>
