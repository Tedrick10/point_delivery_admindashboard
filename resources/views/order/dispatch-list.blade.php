<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter pds-dispatch-list-page">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch card-height pds-page-card">
                    <div class="card-header d-flex justify-content-between align-items-center pds-page-header">
                        <div class="header-title">
                            <h4 class="card-title mb-0 pds-page-title">{{ $pageTitle ?? '' }}</h4>
                        </div>
                        <div class="card-header-toolbar pds-page-actions">
                            <button type="button"
                                    class="pds-dispatch-items-audit-btn"
                                    id="deliAuditBtn"
                                    data-url="{{ route('order.dispatch.deli-audit') }}"
                                    title="{{ __('message.dispatch_audit_deli_amount_log') }}">
                                <i class="fas fa-clipboard-list" aria-hidden="true"></i>
                                <span>{{ __('message.dispatch_audit_deli_amount_log') }}</span>
                            </button>
                            @if(isset($button))
                                {!! $button !!}
                            @endif
                        </div>
                    </div>

                    <div class="card-body pds-page-body">
                        @php
                            $dedicatedStatus = request('dispatch_status');
                            $isDedicatedPickupList = in_array($dedicatedStatus, ['rider_pick_up_error', 'rider_pick_up_cancelled', 'pre_order'], true);
                            $isPreOrderList = $dedicatedStatus === 'pre_order';
                            $pickupListTabs = [
                                'rider_pick_up_unassigned' => __('message.dispatch_tab_pick_up'),
                                'rider_pick_up_assigned' => __('message.dispatch_tab_pick_up_rider'),
                                'rider_pick_up_done' => __('message.dispatch_tab_rider_done'),
                                'admin_completed' => __('message.dispatch_tab_admin_done'),
                            ];
                            $activePickupTab = request('dispatch_status', 'rider_pick_up_unassigned');
                            if (!$isDedicatedPickupList && !array_key_exists($activePickupTab, $pickupListTabs)) {
                                $activePickupTab = 'rider_pick_up_unassigned';
                            }
                            $yangonToday = \Carbon\Carbon::now('Asia/Yangon');
                            if ($isPreOrderList) {
                                $defaultFromDate = $yangonToday->format('d-m-Y');
                                $defaultToDate = $yangonToday->copy()->addDays(14)->format('d-m-Y');
                            } elseif ($isDedicatedPickupList) {
                                $defaultFromDate = $yangonToday->copy()->subDays(30)->format('d-m-Y');
                                $defaultToDate = $yangonToday->format('d-m-Y');
                            } else {
                                $defaultFromDate = $yangonToday->format('d-m-Y');
                                $defaultToDate = $yangonToday->format('d-m-Y');
                            }
                            $filterFromDate = request('from_date', $defaultFromDate);
                            $filterToDate = request('to_date', $defaultToDate);
                            $pickupTabCounts = $isDedicatedPickupList
                                ? []
                                : app(\App\Services\DispatchOrderWorkflowService::class)->orderListTabCounts(
                                    $filterFromDate,
                                    $filterToDate,
                                    request('search_term')
                                );
                            $resetRouteParams = ['orders_type' => request('orders_type', 'list')];
                            if ($isDedicatedPickupList) {
                                $resetRouteParams['dispatch_status'] = $dedicatedStatus;
                            } else {
                                $resetRouteParams['dispatch_status'] = 'rider_pick_up_unassigned';
                            }
                        @endphp
                        <form method="GET" action="{{ route('order.index') }}" id="dispatchFilterForm" class="pds-dispatch-filter-bar">
                            @if(request('orders_type'))
                                <input type="hidden" name="orders_type" value="{{ request('orders_type') }}">
                            @endif
                            @if($isDedicatedPickupList)
                                <input type="hidden" name="dispatch_status" value="{{ $dedicatedStatus }}">
                            @else
                                <input type="hidden" name="dispatch_status" id="dispatch_status" value="{{ $activePickupTab }}">
                            @endif

                            @unless($isDedicatedPickupList)
                            <div class="pds-dispatch-pickup-tabs mb-3" role="tablist" aria-label="{{ __('message.order_list') }}">
                                <div class="pds-dispatch-segment pds-dispatch-pickup-segment">
                                    @foreach($pickupListTabs as $tabKey => $tabLabel)
                                        @php
                                            $tabCount = (int) ($pickupTabCounts[$tabKey] ?? 0);
                                            $tabParams = array_filter([
                                                'orders_type' => request('orders_type', 'list'),
                                                'dispatch_status' => $tabKey,
                                                'from_date' => $filterFromDate,
                                                'to_date' => $filterToDate,
                                                'search_term' => request('search_term'),
                                            ], fn ($v) => $v !== null && $v !== '');
                                        @endphp
                                        <a href="{{ route('order.index', $tabParams) }}"
                                           class="pds-dispatch-segment-btn {{ $activePickupTab === $tabKey ? 'is-active' : '' }}"
                                           role="tab"
                                           aria-selected="{{ $activePickupTab === $tabKey ? 'true' : 'false' }}">
                                            <span class="pds-dispatch-tab-label">{{ $tabLabel }}</span>
                                            <span class="pds-dispatch-tab-count" aria-label="{{ $tabCount }}">{{ $tabCount }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                            @endunless

                            <div class="pds-dispatch-filter-toolbar">
                                <div class="pds-dispatch-filter-group pds-dispatch-filter-group-dates">
                                    <span class="pds-dispatch-filter-group-label">{{ __('message.date') }}</span>
                                    <div class="pds-dispatch-filter-date-range">
                                        <div class="pds-dispatch-filter-control">
                                            <label class="pds-dispatch-filter-sr-only" for="from_date">{{ __('message.from_date') }}</label>
                                            <span class="pds-dispatch-filter-icon" aria-hidden="true"><i class="far fa-calendar-alt"></i></span>
                                            <input type="text" name="from_date" id="from_date"
                                                   class="pds-dispatch-filter-input pds-dispatch-filter-datepicker"
                                                   value="{{ $filterFromDate }}"
                                                   placeholder="{{ __('message.from_date') }}"
                                                   readonly
                                                   autocomplete="off">
                                        </div>
                                        <span class="pds-dispatch-filter-date-sep" aria-hidden="true">—</span>
                                        <div class="pds-dispatch-filter-control">
                                            <label class="pds-dispatch-filter-sr-only" for="to_date">{{ __('message.to_date') }}</label>
                                            <span class="pds-dispatch-filter-icon" aria-hidden="true"><i class="far fa-calendar-alt"></i></span>
                                            <input type="text" name="to_date" id="to_date"
                                                   class="pds-dispatch-filter-input pds-dispatch-filter-datepicker"
                                                   value="{{ $filterToDate }}"
                                                   placeholder="{{ __('message.to_date') }}"
                                                   readonly
                                                   autocomplete="off">
                                        </div>
                                    </div>
                                </div>

                                <div class="pds-dispatch-filter-group pds-dispatch-filter-group-search">
                                    <label class="pds-dispatch-filter-group-label" for="dispatch_search_term">{{ __('message.search') }}</label>
                                    <div class="pds-dispatch-filter-control">
                                        <span class="pds-dispatch-filter-icon" aria-hidden="true"><i class="fas fa-search"></i></span>
                                        <input type="text" name="search_term" id="dispatch_search_term"
                                               class="pds-dispatch-filter-input"
                                               placeholder="{{ __('message.search') }}"
                                               value="{{ request('search_term') }}">
                                    </div>
                                </div>

                                <div class="pds-dispatch-filter-actions">
                                    <button type="submit" class="pds-dispatch-filter-action pds-dispatch-filter-action-primary">
                                        <i class="fas fa-search" aria-hidden="true"></i>
                                        <span>{{ __('message.apply_filter') }}</span>
                                    </button>
                                    <a href="{{ route('order.index', $resetRouteParams) }}"
                                       class="pds-dispatch-filter-action pds-dispatch-filter-action-reset"
                                       title="{{ __('message.reset_filter') }}">
                                        <i class="ri-repeat-line" aria-hidden="true"></i>
                                        <span>{{ __('message.reset_filter') }}</span>
                                    </a>
                                </div>
                            </div>
                        </form>

                        @if(isset($multi_checkbox_delete))
                            <div class="mb-3">{!! $multi_checkbox_delete !!}</div>
                        @endif

                        <div class="pds-table-shell pds-dispatch-table-shell pds-no-freeze">
                            {{ $dataTable->table(['class' => 'table w-100 pds-datatable pds-dispatch-datatable'], false) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="pds-rider-remit-audit pds-deli-audit" id="deliAuditModal" hidden>
        <div class="pds-rider-remit-audit__backdrop" data-deli-audit-close></div>
        <div class="pds-rider-remit-audit__panel" role="dialog" aria-modal="true" aria-labelledby="deliAuditTitle">
            <div class="pds-rider-remit-audit__head">
                <div>
                    <h5 id="deliAuditTitle">{{ __('message.dispatch_audit_deli_amount_log') }}</h5>
                    <p id="deliAuditRangeLabel">{{ $filterFromDate }} — {{ $filterToDate }}</p>
                </div>
                <button type="button" class="pds-rider-remit-audit__close" data-deli-audit-close aria-label="Close">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>
            <div class="pds-rider-remit-audit__body" id="deliAuditBody">
                <p class="pds-rider-remit-audit__empty">{{ __('message.dispatch_audit_deli_amount_empty') }}</p>
            </div>
        </div>
    </div>

    @section('bottom_script')
        {{ $dataTable->scripts() }}
        <script src="{{ asset('js/dispatch-pickup-rider-list.js') }}?v=9"></script>
        <script src="{{ asset('js/admin-order-list-live.js') }}?v=3"></script>
        <script>
            (function bindDeliAuditLog() {
                if (!window.jQuery) {
                    return setTimeout(bindDeliAuditLog, 40);
                }
                var $ = window.jQuery;
                var $btn = $('#deliAuditBtn');
                var $modal = $('#deliAuditModal');
                var $body = $('#deliAuditBody');
                var $range = $('#deliAuditRangeLabel');
                if (!$btn.length || !$modal.length) return;

                var emptyText = @json(__('message.dispatch_audit_deli_amount_empty'));

                function closeAudit() {
                    $modal.attr('hidden', true);
                }

                function openAudit() {
                    var fromDate = $('#from_date').val() || @json($filterFromDate);
                    var toDate = $('#to_date').val() || @json($filterToDate);
                    $range.text(fromDate + ' — ' + toDate);
                    $modal.removeAttr('hidden');
                    $body.html('<p class="pds-rider-remit-audit__loading"><i class="fas fa-spinner fa-spin"></i></p>');
                    $.ajax({
                        url: $btn.data('url'),
                        type: 'GET',
                        data: { from_date: fromDate, to_date: toDate },
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        success: function (res) {
                            var entries = (res && res.entries) ? res.entries : [];
                            if (!entries.length) {
                                $body.html('<p class="pds-rider-remit-audit__empty">' + emptyText + '</p>');
                                return;
                            }
                            var html = '<ol class="pds-rider-remit-audit__list">';
                            entries.forEach(function (e, index) {
                                var parts = Array.isArray(e.action_parts) ? e.action_parts : [];
                                var chips = parts.map(function (p) {
                                    return '<span class="pds-rider-remit-audit__chip">' + $('<div>').text(p).html() + '</span>';
                                }).join('');
                                html += '<li class="pds-rider-remit-audit__item">';
                                html += '<span class="pds-rider-remit-audit__index">' + (index + 1) + '</span>';
                                html += '<div class="pds-rider-remit-audit__card">';
                                html += '<div class="pds-rider-remit-audit__meta">';
                                html += '<strong>' + $('<div>').text(e.title || 'DeliAmount').html() + '</strong>';
                                html += '<time>' + $('<div>').text(e.time || '').html() + '</time>';
                                html += '</div>';
                                html += '<p>' + $('<div>').text(e.summary || e.message || '').html() + '</p>';
                                if (chips) {
                                    html += '<div class="pds-rider-remit-audit__chips">' + chips + '</div>';
                                }
                                html += '</div></li>';
                            });
                            html += '</ol>';
                            $body.html(html);
                        },
                        error: function (xhr) {
                            var msg = (xhr.responseJSON && xhr.responseJSON.message)
                                ? xhr.responseJSON.message
                                : @json(__('message.something_went_wrong'));
                            $body.html('<p class="pds-rider-remit-audit__empty">' + $('<div>').text(msg).html() + '</p>');
                        }
                    });
                }

                $btn.on('click', function (e) {
                    e.preventDefault();
                    openAudit();
                });
                $modal.on('click', '[data-deli-audit-close]', closeAudit);
                $(document).on('keydown.deliAuditList', function (e) {
                    if (e.key === 'Escape' && !$modal.is('[hidden]')) {
                        closeAudit();
                    }
                });
            })();
        </script>
        <script>
            $(document).ready(function () {
                if (typeof flatpickr !== 'undefined') {
                    var allowFutureDates = @json($isPreOrderList);
                    $('.pds-dispatch-filter-datepicker').each(function () {
                        var $input = $(this);
                        var picker = flatpickr(this, {
                            dateFormat: 'd-m-Y',
                            allowInput: false,
                            disableMobile: true,
                            maxDate: allowFutureDates ? null : 'today',
                            defaultDate: $input.val() || null
                        });

                        $input.closest('.pds-dispatch-filter-control')
                            .find('.pds-dispatch-filter-icon')
                            .on('click', function () {
                                picker.open();
                            });
                    });
                }

                var pickupConfig = {
                    assignRouteTemplate: @json(route('order.dispatch.assign-pickup-rider', ['id' => 'ORDER_ID'])),
                    placeholder: @json(__('message.select_name', ['select' => __('message.pickup_rider')])),
                    csrfToken: @json(csrf_token()),
                    errorMessage: @json(__('message.something_went_wrong'))
                };

                function appendDispatchListFilters(data) {
                    data.from_date = $('#from_date').val() || @json($defaultFromDate);
                    data.to_date = $('#to_date').val() || @json($defaultToDate);
                    data.dispatch_status = $('input[name="dispatch_status"]').val()
                        || $('#dispatch_status').val()
                        || @json($isDedicatedPickupList ? ($dedicatedStatus ?? '') : 'rider_pick_up_unassigned');
                    data.search_term = $('input[name="search_term"]').val();
                    if (new URLSearchParams(window.location.search).get('orders_type')) {
                        data.orders_type = new URLSearchParams(window.location.search).get('orders_type');
                    }
                }

                // Bind before first draw so Pickup Rider reload uses the same date filter.
                $(document).on('preXhr.dt', '#dataTableBuilder', function (e, settings, data) {
                    appendDispatchListFilters(data);
                });

                var table = window.LaravelDataTables && window.LaravelDataTables['dataTableBuilder'];
                if (table) {
                    table.on('preXhr.dt', function (e, settings, data) {
                        appendDispatchListFilters(data);
                    });
                }

                $(document).on('click mousedown', '.pds-dispatch-pickup-rider-cell, .pds-dispatch-pickup-rider-cell *', function (e) {
                    e.stopPropagation();
                });

                if (typeof window.bootDispatchPickupRiderList === 'function') {
                    window.bootDispatchPickupRiderList(pickupConfig);
                }

                if (typeof window.bootAdminOrderListLiveRefresh === 'function') {
                    window.bootAdminOrderListLiveRefresh({
                        url: @json(route('order.live-version')),
                        intervalMs: 5000
                    });
                }

                $(document).on('click', '.js-restore-pickup-cancelled', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var $btn = $(this);
                    if ($btn.data('busy')) return;
                    var url = $btn.data('url');
                    if (!url) return;

                    function runRestorePickupCancelled() {
                        $btn.data('busy', true);
                        $.ajax({
                            url: url,
                            method: 'POST',
                            data: { _token: @json(csrf_token()) },
                            success: function (res) {
                                if (window.SnackBar) {
                                    SnackBar({ message: (res && res.message) ? res.message : @json(__('message.pickup_cancelled_restored')), status: 'success' });
                                } else if (window.Swal) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: @json(__('message.success')),
                                        text: (res && res.message) ? res.message : @json(__('message.pickup_cancelled_restored')),
                                        confirmButtonColor: '#FE6F07',
                                        confirmButtonText: @json(__('message.close'))
                                    });
                                } else {
                                    alert((res && res.message) ? res.message : @json(__('message.pickup_cancelled_restored')));
                                }
                                var dt = window.LaravelDataTables && window.LaravelDataTables['dataTableBuilder'];
                                if (dt) dt.ajax.reload(null, false);
                            },
                            error: function (xhr) {
                                var msg = (xhr.responseJSON && xhr.responseJSON.message)
                                    ? xhr.responseJSON.message
                                    : @json(__('message.something_went_wrong'));
                                if (window.SnackBar) {
                                    SnackBar({ message: msg, status: 'error' });
                                } else if (window.Swal) {
                                    Swal.fire({
                                        icon: 'error',
                                        title: @json(__('message.error')),
                                        text: msg,
                                        confirmButtonColor: '#FE6F07'
                                    });
                                } else {
                                    alert(msg);
                                }
                            },
                            complete: function () {
                                $btn.data('busy', false);
                            }
                        });
                    }

                    if (typeof window.Swal !== 'undefined') {
                        Swal.fire({
                            title: @json(__('message.restore_pickup_cancelled')),
                            html: '<p class="pds-swal-confirm-text">' + @json(__('message.restore_pickup_cancelled_confirm')) + '</p>',
                            icon: 'question',
                            showCancelButton: true,
                            focusCancel: true,
                            reverseButtons: true,
                            confirmButtonText: @json(__('message.yes')),
                            cancelButtonText: @json(__('message.cancel')),
                            confirmButtonColor: '#FE6F07',
                            cancelButtonColor: '#94a3b8',
                            buttonsStyling: true,
                            customClass: {
                                popup: 'pds-swal-popup',
                                title: 'pds-swal-title',
                                htmlContainer: 'pds-swal-html',
                                confirmButton: 'pds-swal-confirm',
                                cancelButton: 'pds-swal-cancel',
                                icon: 'pds-swal-icon'
                            }
                        }).then(function (result) {
                            if (result.isConfirmed) {
                                runRestorePickupCancelled();
                            }
                        });
                        return;
                    }

                    if (!window.confirm(@json(__('message.restore_pickup_cancelled_confirm')))) {
                        return;
                    }
                    runRestorePickupCancelled();
                });

                $(document).on('click', '.js-move-pre-pickup-to-order-list', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var $btn = $(this);
                    if ($btn.data('busy')) return;
                    var url = $btn.data('url');
                    if (!url) return;

                    function runMoveToOrderList() {
                        $btn.data('busy', true);
                        $.ajax({
                            url: url,
                            method: 'POST',
                            data: { _token: @json(csrf_token()) },
                            success: function (res) {
                                if (window.SnackBar) {
                                    SnackBar({ message: (res && res.message) ? res.message : @json(__('message.pre_pickup_moved_to_order_list')), status: 'success' });
                                } else if (window.Swal) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: @json(__('message.success')),
                                        text: (res && res.message) ? res.message : @json(__('message.pre_pickup_moved_to_order_list')),
                                        confirmButtonColor: '#FE6F07',
                                        confirmButtonText: @json(__('message.close'))
                                    });
                                } else {
                                    alert((res && res.message) ? res.message : @json(__('message.pre_pickup_moved_to_order_list')));
                                }
                                var dt = window.LaravelDataTables && window.LaravelDataTables['dataTableBuilder'];
                                if (dt) dt.ajax.reload(null, false);
                            },
                            error: function (xhr) {
                                var msg = (xhr.responseJSON && xhr.responseJSON.message)
                                    ? xhr.responseJSON.message
                                    : @json(__('message.something_went_wrong'));
                                if (window.SnackBar) {
                                    SnackBar({ message: msg, status: 'error' });
                                } else if (window.Swal) {
                                    Swal.fire({
                                        icon: 'error',
                                        title: @json(__('message.error')),
                                        text: msg,
                                        confirmButtonColor: '#FE6F07'
                                    });
                                } else {
                                    alert(msg);
                                }
                            },
                            complete: function () {
                                $btn.data('busy', false);
                            }
                        });
                    }

                    if (typeof window.Swal !== 'undefined') {
                        Swal.fire({
                            title: @json(__('message.move_to_order_list')),
                            html: '<p class="pds-swal-confirm-text">' + @json(__('message.move_to_order_list_confirm')) + '</p>',
                            icon: 'question',
                            showCancelButton: true,
                            focusCancel: true,
                            reverseButtons: true,
                            confirmButtonText: @json(__('message.yes')),
                            cancelButtonText: @json(__('message.cancel')),
                            confirmButtonColor: '#FE6F07',
                            cancelButtonColor: '#94a3b8',
                            buttonsStyling: true,
                            customClass: {
                                popup: 'pds-swal-popup',
                                title: 'pds-swal-title',
                                htmlContainer: 'pds-swal-html',
                                confirmButton: 'pds-swal-confirm',
                                cancelButton: 'pds-swal-cancel',
                                icon: 'pds-swal-icon'
                            }
                        }).then(function (result) {
                            if (result.isConfirmed) {
                                runMoveToOrderList();
                            }
                        });
                        return;
                    }

                    if (!window.confirm(@json(__('message.move_to_order_list_confirm')))) {
                        return;
                    }
                    runMoveToOrderList();
                });
            });
        </script>
    @endsection
</x-master-layout>
