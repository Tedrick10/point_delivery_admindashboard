<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter pds-dispatch-to-assign-page">
        <div class="pds-dispatch-to-assign-screen">
            <div class="pds-dispatch-to-assign-topbar">
                <div class="pds-dispatch-to-assign-topbar-copy">
                    <h4 class="pds-dispatch-to-assign-heading">{{ $pageTitle ?? __('message.to_assign') }}</h4>
                    <p class="pds-dispatch-to-assign-subtitle">
                        {{ __('message.item_count') }} = <strong id="toAssignItemCount">{{ $items->count() }}</strong>
                    </p>
                </div>
                <div class="pds-dispatch-to-assign-topbar-actions">
                    <button type="button" class="pds-dispatch-items-icon-btn" onclick="window.print()" title="{{ __('message.print') }}">
                        <i class="fas fa-print"></i>
                    </button>
                </div>
            </div>

            <div class="pds-dispatch-to-assign-filter">
                <div class="pds-dispatch-to-assign-filter-grid">
                    <div class="pds-dispatch-field pds-dispatch-field-sm">
                        <label for="to_assign_status">{{ __('message.status') }}</label>
                        <select id="to_assign_status" class="pds-dispatch-input pds-dispatch-select">
                            <option value="">{{ __('message.all') }}</option>
                            <option value="pick_up" selected>{{ __('message.follow_up_status_pick_up') }}</option>
                            <option value="assigned">{{ __('message.follow_up_status_assigned') }}</option>
                            <option value="assigned_100">{{ __('message.follow_up_status_assigned_100') }}</option>
                            <option value="on_way">{{ __('message.follow_up_status_on_way') }}</option>
                            <option value="pending">{{ __('message.follow_up_status_pending') }}</option>
                            <option value="delivered">{{ __('message.follow_up_status_delivered') }}</option>
                            <option value="completed">{{ __('message.follow_up_status_completed') }}</option>
                            <option value="finished">{{ __('message.follow_up_status_finished') }}</option>
                            <option value="cancelled">{{ __('message.cancelled') }}</option>
                        </select>
                    </div>
                    <div class="pds-dispatch-to-assign-os-filter">
                        <div class="pds-dispatch-field pds-dispatch-field-sm">
                            <label for="to_assign_os_name">{{ __('message.os_name') }}</label>
                            <input type="text" id="to_assign_os_name" class="pds-dispatch-input" placeholder="{{ __('message.os_name') }}" autocomplete="off">
                        </div>
                        <button type="button" class="pds-dispatch-to-assign-os-search-btn" id="openToAssignOsSearch" title="{{ __('message.to_find_os_name') }}">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <div class="pds-dispatch-field pds-dispatch-field-sm">
                        <label for="to_assign_pickup_rider">{{ __('message.pickup_rider') }}</label>
                        <input type="text" id="to_assign_pickup_rider" class="pds-dispatch-input" placeholder="{{ __('message.pickup_rider') }}" autocomplete="off">
                    </div>
                    <div class="pds-dispatch-field pds-dispatch-field-sm">
                        <label for="to_assign_customer_search">{{ __('message.customer_name') }} / {{ __('message.phone') }}</label>
                        <input type="text" id="to_assign_customer_search" class="pds-dispatch-input" placeholder="{{ __('message.customer_name') }} / {{ __('message.phone') }}" autocomplete="off">
                    </div>
                </div>
            </div>

            <div class="pds-dispatch-to-assign-body">
                @if($items->isEmpty())
                    <div class="pds-dispatch-to-assign-empty" id="toAssignEmptyState">
                        <i class="fas fa-inbox"></i>
                        <p>{{ __('message.no_record_found') }}</p>
                    </div>
                @else
                    <div class="pds-dispatch-to-assign-empty d-none" id="toAssignFilterEmptyState">
                        <i class="fas fa-search"></i>
                        <p>{{ __('message.no_record_found') }}</p>
                    </div>
                    <div class="pds-dispatch-to-assign-table-shell pds-table-shell" id="toAssignTableShell">
                        <table class="table w-100 pds-dispatch-to-assign-table" id="toAssignTable">
                            <thead>
                                <tr>
                                    <th>{{ __('message.no') }}</th>
                                    <th>{{ __('message.os_name') }}</th>
                                    <th>{{ __('message.os_phone') }}</th>
                                    <th>{{ __('message.os_address') }}</th>
                                    <th>{{ __('message.customer_name') }}</th>
                                    <th>{{ __('message.phone') }}</th>
                                    <th>{{ __('message.address') }}</th>
                                    <th>{{ __('message.admin_status') }}</th>
                                    <th>{{ __('message.rider_status') }}</th>
                                    <th>{{ __('message.status') }}</th>
                                    <th>{{ __('message.item_value') }}</th>
                                    <th>{{ __('message.deli_amount') }}</th>
                                    <th>{{ __('message.pickup_rider') }}</th>
                                    <th>{{ __('message.delivery_rider') }}</th>
                                    <th>{{ __('message.details') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $index => $item)
                                    @php
                                        $order = $item->order;
                                        $workflow = app(\App\Services\DispatchOrderWorkflowService::class);
                                        $osName = resolveDispatchOsName($order);
                                        $osPhone = resolveDispatchOsPhone($order);
                                        $osAddress = resolveDispatchOsAddress($order);
                                        $pickupRider = optional($order?->delivery_man)->name ?? '-';
                                        $deliveryRider = optional($item->deliveryMan)->name ?? '-';
                                    @endphp
                                    <tr
                                        data-os-name="{{ $osName !== '-' ? $osName : '' }}"
                                        data-pickup-rider="{{ $pickupRider !== '-' ? $pickupRider : '' }}"
                                        data-customer-name="{{ $item->customer_name ?: '' }}"
                                        data-customer-phone="{{ $item->customer_phone ?: '' }}"
                                        data-follow-up-status="{{ $workflow->followUpStatusKey($item) }}"
                                    >
                                        <td class="to-assign-row-no">{{ $index + 1 }}</td>
                                        <td>{{ $osName }}</td>
                                        <td>{{ $osPhone }}</td>
                                        <td class="pds-follow-up-address-cell" title="{{ $osAddress }}">{{ stringLong($osAddress, 'title', 28) ?: '-' }}</td>
                                        <td>{{ $item->customer_name ?: '-' }}</td>
                                        <td>{{ $item->customer_phone ?: '-' }}</td>
                                        <td class="pds-follow-up-address-cell" title="{{ $item->customer_address }}">{{ stringLong($item->customer_address ?? '', 'title', 28) ?: '-' }}</td>
                                        <td>
                                            @if($order)
                                                @include('order.dispatch-admin-status', ['order' => $order])
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($order)
                                                @include('order.dispatch-rider-status', ['order' => $order])
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            <span class="pds-dispatch-status {{ $workflow->followUpItemStatusClass($item) }}">{{ $workflow->followUpItemStatusLabel($item) }}</span>
                                        </td>
                                        <td>{{ number_format((float) $item->item_value) }}</td>
                                        <td class="text-right">{!! formatDispatchDeliAmountHtml($item) !!}</td>
                                        <td>{{ $pickupRider }}</td>
                                        <td>{{ $deliveryRider }}</td>
                                        <td>
                                            <button type="button"
                                                    class="pds-follow-up-details-btn"
                                                    data-target="#followUpDetails-{{ $item->id }}">
                                                {{ __('message.details') }}
                                            </button>
                                            <div class="d-none" id="followUpDetails-{{ $item->id }}">
                                                @include('order.partials._follow_up_details_content', ['item' => $item])
                                            </div>
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

    <div class="modal fade" id="followUpDetailsModal" tabindex="-1" role="dialog" aria-labelledby="followUpDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content pds-follow-up-details-modal">
                <div class="modal-header">
                    <h5 class="modal-title" id="followUpDetailsModalLabel">{{ __('message.details') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('message.close') }}">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="followUpDetailsModalBody"></div>
            </div>
        </div>
    </div>

    @section('bottom_script')
        <script src="{{ asset('js/dispatch-os-fields.js') }}?v=3"></script>
        <script>
            (function () {
                var osSearchRoute = "{{ route('ajax-list', ['type' => 'os_dispatch_search']) }}";

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

                function normalizeFilterValue(value) {
                    return String(value || '').toLowerCase().trim();
                }

                function applyToAssignFilters() {
                    var osTerm = normalizeFilterValue($('#to_assign_os_name').val());
                    var riderTerm = normalizeFilterValue($('#to_assign_pickup_rider').val());
                    var customerTerm = normalizeFilterValue($('#to_assign_customer_search').val());
                    var statusTerm = String($('#to_assign_status').val() || '').trim();
                    var visibleCount = 0;

                    $('#toAssignTable tbody tr').each(function () {
                        var $row = $(this);
                        var rowOsName = normalizeFilterValue($row.data('os-name'));
                        var rowPickupRider = normalizeFilterValue($row.data('pickup-rider'));
                        var rowCustomerName = normalizeFilterValue($row.data('customer-name'));
                        var rowCustomerPhone = normalizeFilterValue($row.data('customer-phone'));
                        var rowStatus = String($row.data('follow-up-status') || '');
                        var statusMatch = !statusTerm || rowStatus === statusTerm;
                        var isMatch = (!osTerm || rowOsName.indexOf(osTerm) !== -1)
                            && (!riderTerm || rowPickupRider.indexOf(riderTerm) !== -1)
                            && (!customerTerm || rowCustomerName.indexOf(customerTerm) !== -1 || rowCustomerPhone.indexOf(customerTerm) !== -1)
                            && statusMatch;

                        $row.toggleClass('d-none', !isMatch);
                        if (isMatch) {
                            visibleCount += 1;
                            $row.find('.to-assign-row-no').text(visibleCount);
                        }
                    });

                    $('#toAssignItemCount').text(visibleCount);
                    $('#toAssignFilterEmptyState').toggleClass('d-none', visibleCount > 0);
                    $('#toAssignTableShell').toggleClass('d-none', visibleCount === 0);
                }

                if (typeof window.initDispatchOsFields === 'function') {
                    window.initDispatchOsFields({ osSearchRoute: osSearchRoute });
                }

                $('#openToAssignOsSearch').on('click', openOsSearchModal);

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

                $(document).on('click', '#osSearchTableBody .pds-dispatch-table-row', function () {
                    var item = $(this).data('item');
                    if (!item) {
                        return;
                    }
                    var osName = item.name || String(item.text || '').replace(/\s*\([^)]*\)\s*$/, '').trim();
                    $('#to_assign_os_name').val(osName);
                    $('#osSearchModal').modal('hide');
                    applyToAssignFilters();
                });

                var toAssignFilterTimer;
                $('#to_assign_os_name, #to_assign_pickup_rider, #to_assign_customer_search, #to_assign_status').on('input change', function () {
                    clearTimeout(toAssignFilterTimer);
                    toAssignFilterTimer = setTimeout(applyToAssignFilters, 150);
                });

                applyToAssignFilters();

                $(document).on('click', '.pds-follow-up-details-btn', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var target = $(this).data('target');
                    var $content = $(target);
                    if (!$content.length) {
                        return;
                    }
                    $('#followUpDetailsModalBody').html($content.html());
                    $('#followUpDetailsModal').modal('show');
                });

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
            })();
        </script>
    @endsection
</x-master-layout>
