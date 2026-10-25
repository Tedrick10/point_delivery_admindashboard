<x-master-layout :assets="$assets ?? []">
    
{{-- data-live-mode=ajax: live poll refreshes rows only (no full shell soft-replace / CSS flicker) --}}
<div id="adminLiveRoot" data-live-page="follow-up" data-live-mode="ajax">
<style>
        .pds-kyo-shin-row-badge {
            display: inline-flex; align-items: center; margin-left: 6px;
            padding: 2px 7px; border-radius: 999px; font-size: 10px; font-weight: 800;
            background: rgba(var(--brand-rgb), 0.16); color: var(--site-color); letter-spacing: .02em;
        }
        .pds-follow-up-loading {
            display: flex; align-items: center; justify-content: center; gap: 0.65rem;
            min-height: 160px; color: #64748b; font-size: 0.9rem;
        }
        .pds-follow-up-loading i { animation: pds-fu-spin 0.8s linear infinite; }
        @keyframes pds-fu-spin { to { transform: rotate(360deg); } }
        .pds-follow-up-pager {
            display: flex; align-items: center; justify-content: space-between; gap: 0.85rem;
            flex-wrap: wrap; margin-top: 1rem; padding: 0.85rem 1rem;
            border: 1px solid #e8eef5; border-radius: 14px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        }
        .pds-follow-up-pager__meta {
            display: inline-flex; align-items: center; gap: 0.45rem;
            color: #64748b; font-size: 0.8rem; font-weight: 500;
        }
        .pds-follow-up-pager__meta strong {
            color: #0f172a; font-weight: 700;
        }
        .pds-follow-up-pager__nav {
            display: inline-flex; align-items: center; gap: 0.35rem;
            flex-wrap: wrap;
        }
        .pds-follow-up-pager__pages {
            display: inline-flex; align-items: center; gap: 0.28rem;
            flex-wrap: wrap;
        }
        .pds-follow-up-pager__btn,
        .pds-follow-up-pager__page {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 38px; height: 38px; padding: 0 0.6rem;
            border-radius: 11px; border: 1px solid transparent;
            background: transparent; color: #0f172a;
            font-size: 0.86rem; font-weight: 700; line-height: 1;
            cursor: pointer; transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .pds-follow-up-pager__btn {
            min-width: 38px; color: #0f172a; border-color: #cbd5e1; background: #fff;
        }
        .pds-follow-up-pager__page {
            border-color: transparent;
        }
        .pds-follow-up-pager__btn:hover:not(:disabled),
        .pds-follow-up-pager__page:hover:not(.is-active):not(:disabled) {
            border-color: var(--site-color, #FE6F07);
            color: var(--site-color, #FE6F07);
            background: rgba(var(--brand-rgb), 0.1);
        }
        .pds-follow-up-pager__page.is-active {
            background: var(--site-color, #FE6F07) !important;
            color: #ffffff !important;
            border-color: var(--site-color, #FE6F07) !important;
            box-shadow: 0 6px 16px rgba(var(--brand-rgb), 0.42);
            cursor: default;
        }
        .pds-follow-up-pager__btn:disabled {
            opacity: 0.4; cursor: not-allowed; box-shadow: none;
        }
        .pds-follow-up-pager__page.is-active:disabled {
            opacity: 1; cursor: default;
        }
        .pds-follow-up-page.is-fu-silent-loading #toAssignTableShell {
            opacity: 0.72;
            pointer-events: none;
            transition: opacity 0.15s ease;
        }
        .pds-follow-up-pager__ellipsis {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 28px; height: 36px; color: #94a3b8; font-weight: 700; letter-spacing: 0.08em;
            user-select: none;
        }
        @media (max-width: 640px) {
            .pds-follow-up-pager { justify-content: center; }
            .pds-follow-up-pager__meta { width: 100%; justify-content: center; }
            .pds-follow-up-pager__nav { width: 100%; justify-content: center; }
        }
        /* Orange table header sticks while scrolling the Follow Up table shell */
        .pds-follow-up-page .pds-dispatch-to-assign-table-shell {
            overflow: auto !important;
            max-height: min(68vh, calc(100vh - var(--pds-topbar-h, 73px) - 220px)) !important;
            /* transform/contain break position:sticky on thead */
            transform: none !important;
            contain: none !important;
        }
        .pds-follow-up-page .pds-dispatch-to-assign-table.pds-sticky-freeze {
            border-collapse: separate !important;
            border-spacing: 0 !important;
        }
        .pds-follow-up-page .pds-dispatch-to-assign-table thead th {
            position: sticky !important;
            top: 0 !important;
            z-index: 6 !important;
            background: var(--site-color, #FE6F07) !important;
            color: #fff !important;
        }
    </style>
    <div class="container-fluid pds-page-wrap pds-dispatch-to-assign-page pds-follow-up-page">
        <div class="pds-dispatch-to-assign-screen">
            <div class="pds-follow-up-page-head">
            <div class="pds-dispatch-to-assign-topbar">
                <div class="pds-dispatch-to-assign-topbar-copy">
                    <span class="pds-dispatch-to-assign-eyebrow">
                        <i class="fas fa-route" aria-hidden="true"></i>
                        {{ __('message.order') }}
                    </span>
                    <h4 class="pds-dispatch-to-assign-heading">{{ $pageTitle ?? __('message.to_assign') }}</h4>
                    <p class="pds-dispatch-to-assign-subtitle">
                        <span class="pds-dispatch-to-assign-count-pill">
                            {{ __('message.item_count') }}
                            <strong id="toAssignItemCount">0</strong>
                        </span>
                    </p>
                </div>
                @php
                    $canReassignFollowUpRider = $canReassignFollowUpRider ?? (auth()->user()->can('order-edit') || auth()->user()->user_type === 'admin');
                @endphp
                <div class="pds-dispatch-to-assign-topbar-actions">
                    @if($canReassignFollowUpRider)
                        <button type="button" class="pds-assign-action-btn pds-assign-action-btn--rider" id="followUpAssignRiderBtn" disabled>
                            <span class="pds-assign-action-btn__icon" aria-hidden="true">
                                <i class="fas fa-motorcycle"></i>
                            </span>
                            <span class="pds-assign-action-btn__label">{{ __('message.follow_up_assign_rider') }}</span>
                        </button>
                    @endif
                    <button type="button" class="pds-dispatch-items-icon-btn" onclick="window.print()" title="{{ __('message.print') }}">
                        <i class="fas fa-print"></i>
                    </button>
                </div>
            </div>

            <div class="pds-dispatch-to-assign-filter">
                <div class="pds-dispatch-to-assign-filter-grid">
                    <div class="pds-dispatch-field pds-dispatch-field-sm pds-follow-up-filter-status">
                        <label for="to_assign_status">{{ __('message.status') }}</label>
                        <select id="to_assign_status" class="pds-dispatch-input pds-dispatch-select">
                            <option value="" selected>{{ __('message.all') }}</option>
                            <option value="pick_up">{{ __('message.follow_up_status_pick_up') }}</option>
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
                            <div class="pds-follow-up-os-input-wrap">
                                <input type="text" id="to_assign_os_name" class="pds-dispatch-input" placeholder="{{ __('message.os_name') }} / {{ __('message.phone') }}" autocomplete="off">
                                <button type="button" class="pds-dispatch-to-assign-os-search-btn" id="openToAssignOsSearch" title="{{ __('message.to_find_os_name') }}">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="pds-dispatch-field pds-dispatch-field-sm">
                        <label for="to_assign_pickup_rider">{{ __('message.pickup_rider') }}</label>
                                <input type="text" id="to_assign_pickup_rider" class="pds-dispatch-input" placeholder="{{ __('message.pickup_rider') }} / {{ __('message.phone') }}" autocomplete="off">
                    </div>
                    <div class="pds-dispatch-field pds-dispatch-field-sm pds-follow-up-filter-customer">
                        <label for="to_assign_customer_search">{{ __('message.customer_name') }} / {{ __('message.phone') }}</label>
                        <input type="text" id="to_assign_customer_search" class="pds-dispatch-input" placeholder="{{ __('message.customer_name') }} / {{ __('message.phone') }}" autocomplete="off">
                    </div>
                </div>
            </div>
            </div>{{-- /.pds-follow-up-page-head --}}

            <div class="pds-dispatch-to-assign-body">
                <div class="pds-dispatch-to-assign-empty d-none" id="toAssignEmptyState">
                        <i class="fas fa-inbox"></i>
                        <p>{{ __('message.no_record_found') }}</p>
                    </div>
                <div class="pds-follow-up-loading" id="toAssignLoadingState" aria-live="polite">
                    <i class="fas fa-spinner" aria-hidden="true"></i>
                    <span>{{ __('message.splash_loading') }}</span>
                    </div>
                <div class="pds-dispatch-to-assign-table-shell pds-table-shell d-none" id="toAssignTableShell">
                    <table class="table w-100 pds-dispatch-to-assign-table pds-sticky-freeze" id="toAssignTable">
                            <thead>
                                <tr>
                                    @if($canReassignFollowUpRider)
                                        <th class="pds-fu-col pds-fu-col--check pds-follow-up-check-col">
                                            <input type="checkbox" id="followUpSelectAll" aria-label="{{ __('message.all') }}">
                                        </th>
                                    @endif
                                    <th class="pds-fu-col pds-fu-col--no">{{ __('message.no') }}</th>
                                    <th class="pds-fu-col pds-fu-col--date">{{ __('message.order') }}</th>
                                    <th class="pds-fu-col pds-fu-col--date">{{ __('message.received_date') }}</th>
                                    <th class="pds-fu-col pds-fu-col--voucher">{{ __('message.voucher_code') }}</th>
                                    <th class="pds-fu-col pds-fu-col--status">{{ __('message.status') }}</th>
                                    <th class="pds-fu-col pds-fu-col--fromto">{{ __('message.from_to') }}</th>
                                    <th class="pds-fu-col pds-fu-col--os">{{ __('message.os_name') }}</th>
                                    <th class="pds-fu-col pds-fu-col--os-addr">{{ __('message.os_address') }}</th>
                                    <th class="pds-fu-col pds-fu-col--pickup">{{ __('message.pickup') }}</th>
                                    <th class="pds-fu-col pds-fu-col--customer">{{ __('message.customer_name') }}</th>
                                    <th class="pds-fu-col pds-fu-col--phone">{{ __('message.phone') }}</th>
                                    <th class="pds-fu-col pds-fu-col--addr">{{ __('message.address') }}</th>
                                    <th class="pds-fu-col pds-fu-col--township">{{ __('message.township') }}</th>
                                    <th class="pds-fu-col pds-fu-col--rider">{{ __('message.delivery_man') }}</th>
                                    <th class="pds-fu-col pds-fu-col--money">{{ __('message.cust_paid') }}</th>
                                    <th class="pds-fu-col pds-fu-col--money">{{ __('message.item_value') }}</th>
                                    <th class="pds-fu-col pds-fu-col--deli">{{ __('message.deli_amount') }}</th>
                                    <th class="pds-fu-col pds-fu-col--remark">{{ __('message.remark_label') }}</th>
                                    <th class="pds-fu-col pds-fu-col--action">{{ __('message.action') }}</th>
                                </tr>
                            </thead>
                        <tbody id="toAssignTableBody"></tbody>
                        </table>
                    </div>
                <div class="pds-follow-up-pager d-none" id="toAssignPager">
                    <div class="pds-follow-up-pager__meta" id="toAssignPagerMeta"></div>
                    <nav class="pds-follow-up-pager__nav" aria-label="{{ __('message.follow_up') }} pagination">
                        <button type="button" class="pds-follow-up-pager__btn" id="toAssignPrevPage" title="{{ __('message.previous') }}" aria-label="{{ __('message.previous') }}">
                            <i class="fas fa-chevron-left" aria-hidden="true"></i>
                        </button>
                        <div class="pds-follow-up-pager__pages" id="toAssignPagerPages"></div>
                        <button type="button" class="pds-follow-up-pager__btn" id="toAssignNextPage" title="{{ __('message.next') }}" aria-label="{{ __('message.next') }}">
                            <i class="fas fa-chevron-right" aria-hidden="true"></i>
                        </button>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    @include('order.partials._os_search_modal')
    @include('order.partials._pending-remark-history-modal')
    @if($canReassignFollowUpRider ?? false)
        @include('order.partials._rider_assign_modal')
    @endif

    <div class="modal fade" id="followUpDetailsModal" tabindex="-1" role="dialog" aria-labelledby="followUpDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content pds-follow-up-details-modal">
                <div class="modal-header pds-follow-up-details-modal__header">
                    <h5 class="modal-title" id="followUpDetailsModalLabel">{{ __('message.details') }}</h5>
                    <div class="pds-follow-up-details-header-actions" id="followUpDetailsHeaderActions" aria-label="{{ __('message.details') }}"></div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('message.close') }}">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="followUpDetailsModalBody"></div>
            </div>
        </div>
    </div>
</div>


    @section('bottom_script')
        <script src="{{ asset('js/dispatch-os-fields.js') }}?v=10"></script>
        <script>
            (function () {
                var osSearchRoute = "{{ route('ajax-list', ['type' => 'os_dispatch_search']) }}";
                var riderRoute = "{{ route('ajax-list', ['type' => 'dispatch_deliveryman_search']) }}";
                var reassignUrl = @json(route('order.dispatch.to-assign.reassign-rider'));
                var dataUrl = @json($followUpDataUrl ?? route('order.dispatch.to-assign.data'));
                var canReassign = @json((bool) ($canReassignFollowUpRider ?? false));
                var riderCache = [];
                var pendingItemIds = [];
                var activeToBranchId = 0;
                var currentPage = 1;
                var lastPage = 1;
                var loadSeq = 0;
                var filterTimer = null;
                var loadXhr = null;
                var hasLoadedOnce = false;

                // Live poll must NOT flash the full-page spinner every few seconds.
                if (typeof window.adminLiveUpdateOptions === 'function') {
                    window.adminLiveUpdateOptions({ refreshContent: false });
                }

                window.reloadDispatchItemsTable = function () {
                    loadFollowUpPage(currentPage, { silent: true });
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
                    var query = String(term || '').trim();
                    if (!query) return riderCache;
                    var matchFn = typeof window.pdsMatchNameOrPhone === 'function'
                        ? window.pdsMatchNameOrPhone
                        : null;
                    return riderCache.filter(function (row) {
                        var name = row.name || row.text || '';
                        var phone = row.phone || '';
                        if (matchFn) {
                            return matchFn(query, name, phone)
                                || String(row.city || '').toLowerCase().indexOf(query.toLowerCase()) !== -1
                                || String(row.branch || '').toLowerCase().indexOf(query.toLowerCase()) !== -1;
                        }
                        var q = query.toLowerCase();
                        return String(name).toLowerCase().indexOf(q) !== -1
                            || String(phone).toLowerCase().indexOf(q) !== -1
                            || String(row.city || '').toLowerCase().indexOf(q) !== -1
                            || String(row.branch || '').toLowerCase().indexOf(q) !== -1;
                    });
                }

                function loadRiders(callback) {
                    var params = {};
                    if (activeToBranchId > 0) params.branch_id = activeToBranchId;
                    $.get(riderRoute, params, function (res) {
                        riderCache = res.results || [];
                        if (typeof callback === 'function') callback(riderCache);
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

                function getVisibleCheckedItemIds() {
                    var ids = [];
                    $('#toAssignTableBody .pds-follow-up-item-check:checked').each(function () {
                        ids.push(parseInt($(this).val(), 10));
                    });
                    return ids;
                }

                function updateAssignRiderButtonState() {
                    if (!canReassign) return;
                    var count = getVisibleCheckedItemIds().length;
                    $('#followUpAssignRiderBtn').prop('disabled', count === 0);
                    var visibleAssignable = $('#toAssignTableBody tr[data-can-reassign="1"]').length;
                    var checkedVisible = $('#toAssignTableBody tr[data-can-reassign="1"] .pds-follow-up-item-check:checked').length;
                    $('#followUpSelectAll').prop('checked', visibleAssignable > 0 && checkedVisible === visibleAssignable);
                }

                function openRiderAssignModal(itemIds, toBranchId) {
                    pendingItemIds = itemIds || [];
                    activeToBranchId = parseInt(toBranchId, 10) || 0;
                    if (!pendingItemIds.length) {
                        if (typeof errorMessage === 'function') {
                            errorMessage(@json(__('message.follow_up_select_to_assign')));
                        }
                        return;
                    }
                    $('#rider_modal_search').val('');
                    loadRiders(function (rows) {
                        renderRiderRows(rows);
                    });
                    $('#riderAssignModal').modal('show');
                }

                function submitReassign(riderId) {
                    if (!pendingItemIds.length || !riderId) return;
                    $('#followUpAssignRiderBtn').prop('disabled', true);
                    $.ajax({
                        url: reassignUrl,
                        type: 'POST',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content'),
                            item_ids: pendingItemIds,
                            delivery_man_id: riderId
                        },
                        success: function (res) {
                            $('#riderAssignModal').modal('hide');
                            pendingItemIds = [];
                            if (res && res.message && typeof showMessage === 'function') {
                                showMessage(res.message);
                            }
                            loadFollowUpPage(currentPage);
                        },
                        error: function (xhr) {
                            updateAssignRiderButtonState();
                            var msg = (xhr.responseJSON && xhr.responseJSON.message)
                                ? xhr.responseJSON.message
                                : '{{ __('message.something_went_wrong') }}';
                            if (typeof errorMessage === 'function') errorMessage(msg);
                        }
                    });
                }

                function currentFilters() {
                    return {
                        status: String($('#to_assign_status').val() || '').trim(),
                        os: String($('#to_assign_os_name').val() || '').trim(),
                        pickup_rider: String($('#to_assign_pickup_rider').val() || '').trim(),
                        customer: String($('#to_assign_customer_search').val() || '').trim(),
                        page: currentPage,
                        per_page: 50
                    };
                }

                function setLoading(isLoading, silent) {
                    var $page = $('.pds-follow-up-page');
                    if (silent) {
                        $page.toggleClass('is-fu-silent-loading', !!isLoading);
                        return;
                    }
                    $page.removeClass('is-fu-silent-loading');
                    $('#toAssignLoadingState').toggleClass('d-none', !isLoading);
                    if (isLoading) {
                        $('#toAssignEmptyState').addClass('d-none');
                        $('#toAssignTableShell').addClass('d-none');
                        $('#toAssignPager').addClass('d-none');
                    }
                }

                function buildPageWindow(page, totalPages) {
                    var pages = [];
                    if (totalPages <= 7) {
                        for (var i = 1; i <= totalPages; i++) pages.push(i);
                        return pages;
                    }
                    pages.push(1);
                    var start = Math.max(2, page - 1);
                    var end = Math.min(totalPages - 1, page + 1);
                    if (page <= 3) {
                        start = 2;
                        end = 4;
                    }
                    if (page >= totalPages - 2) {
                        start = totalPages - 3;
                        end = totalPages - 1;
                    }
                    if (start > 2) pages.push('…');
                    for (var n = start; n <= end; n++) pages.push(n);
                    if (end < totalPages - 1) pages.push('…');
                    pages.push(totalPages);
                    return pages;
                }

                function renderPager(from, to, total) {
                    var $pages = $('#toAssignPagerPages').empty();
                    var windowPages = buildPageWindow(currentPage, lastPage);
                    windowPages.forEach(function (item) {
                        if (item === '…') {
                            $pages.append('<span class="pds-follow-up-pager__ellipsis" aria-hidden="true">…</span>');
                            return;
                        }
                        var isActive = item === currentPage;
                        var $btn = $('<button type="button" class="pds-follow-up-pager__page"></button>')
                            .text(String(item))
                            .attr('data-page', item)
                            .attr('aria-label', 'Page ' + item);
                        if (isActive) {
                            $btn.addClass('is-active').attr('aria-current', 'page').prop('disabled', true);
                        }
                        $pages.append($btn);
                    });
                    $('#toAssignPagerMeta').html(
                        '<span>Showing <strong>' + (from || 0) + '–' + (to || 0) + '</strong> of <strong>' + total + '</strong></span>'
                    );
                    $('#toAssignPrevPage').prop('disabled', currentPage <= 1);
                    $('#toAssignNextPage').prop('disabled', currentPage >= lastPage);
                }

                function loadFollowUpPage(page, options) {
                    options = options || {};
                    var silent = !!options.silent && hasLoadedOnce;
                    currentPage = Math.max(1, parseInt(page, 10) || 1);
                    var seq = ++loadSeq;
                    var params = currentFilters();
                    params.page = currentPage;
                    if (loadXhr && typeof loadXhr.abort === 'function') {
                        try { loadXhr.abort(); } catch (e) {}
                    }
                    setLoading(true, silent);
                    loadXhr = $.ajax({
                        url: dataUrl,
                        method: 'GET',
                        data: params,
                        dataType: 'json',
                        cache: false
                    }).done(function (res) {
                            if (seq !== loadSeq) return;
                            var total = parseInt(res.total, 10) || 0;
                            currentPage = parseInt(res.page, 10) || 1;
                            lastPage = parseInt(res.last_page, 10) || 1;
                            $('#toAssignItemCount').text(total);
                            $('#toAssignTableBody').html(res.html || '');
                            $('#followUpSelectAll').prop('checked', false);
                            hasLoadedOnce = true;
                            setLoading(false, silent);
                            if (total === 0) {
                                $('#toAssignEmptyState').removeClass('d-none');
                                $('#toAssignTableShell').addClass('d-none');
                                $('#toAssignPager').addClass('d-none');
                            } else {
                                $('#toAssignEmptyState').addClass('d-none');
                                $('#toAssignTableShell').removeClass('d-none');
                                $('#toAssignPager').removeClass('d-none');
                                renderPager(res.from || 0, res.to || 0, total);
                            }
                    updateAssignRiderButtonState();
                        })
                        .fail(function (xhr, status) {
                            if (seq !== loadSeq || status === 'abort') return;
                            setLoading(false, silent);
                            if (!hasLoadedOnce) {
                                $('#toAssignEmptyState').removeClass('d-none');
                                $('#toAssignTableBody').empty();
                            }
                            if (typeof errorMessage === 'function') {
                                errorMessage('{{ __('message.something_went_wrong') }}');
                            }
                        })
                        .always(function () {
                            if (seq === loadSeq) loadXhr = null;
                        });
                }

                function scheduleReload(resetPage) {
                    clearTimeout(filterTimer);
                    filterTimer = setTimeout(function () {
                        if (resetPage) currentPage = 1;
                        loadFollowUpPage(currentPage);
                    }, 220);
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
                    if (!item) return;
                    var osName = item.name || String(item.text || '').replace(/\s*\([^)]*\)\s*$/, '').trim();
                    $('#to_assign_os_name').val(osName);
                    $('#osSearchModal').modal('hide');
                    scheduleReload(true);
                });

                $('#to_assign_os_name, #to_assign_pickup_rider, #to_assign_customer_search').on('input', function () {
                    scheduleReload(true);
                });
                $('#to_assign_status').on('change', function () {
                    scheduleReload(true);
                });

                $('#toAssignPrevPage').on('click', function () {
                    if (currentPage > 1) loadFollowUpPage(currentPage - 1);
                });
                $('#toAssignNextPage').on('click', function () {
                    if (currentPage < lastPage) loadFollowUpPage(currentPage + 1);
                });
                $(document).on('click', '#toAssignPagerPages .pds-follow-up-pager__page', function () {
                    if ($(this).hasClass('is-active') || $(this).prop('disabled')) return;
                    var page = parseInt($(this).attr('data-page'), 10);
                    if (page && page !== currentPage) loadFollowUpPage(page);
                });

                $(document).on('change', '#followUpSelectAll', function () {
                    var checked = $(this).is(':checked');
                    $('#toAssignTableBody tr[data-can-reassign="1"] .pds-follow-up-item-check').prop('checked', checked);
                    updateAssignRiderButtonState();
                });

                $(document).on('change', '.pds-follow-up-item-check', updateAssignRiderButtonState);

                $('#followUpAssignRiderBtn').on('click', function () {
                    var ids = getVisibleCheckedItemIds();
                    if (!ids.length) {
                        if (typeof errorMessage === 'function') {
                            errorMessage(@json(__('message.follow_up_select_to_assign')));
                        }
                        return;
                    }
                    var branchIds = {};
                    $('#toAssignTableBody .pds-follow-up-item-check:checked').each(function () {
                        var bid = parseInt($(this).closest('tr').data('to-branch-id'), 10) || 0;
                        if (bid > 0) branchIds[bid] = true;
                    });
                    var uniqueBranches = Object.keys(branchIds);
                    openRiderAssignModal(ids, uniqueBranches.length === 1 ? uniqueBranches[0] : 0);
                });

                $(document).on('click', '.js-follow-up-assign-one', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var itemId = parseInt($(this).data('item-id'), 10);
                    var branchId = parseInt($(this).data('to-branch-id'), 10) || 0;
                    if (!itemId) return;
                    openRiderAssignModal([itemId], branchId);
                });

                var riderSearchTimer;
                $('#rider_modal_search').on('input', function () {
                    clearTimeout(riderSearchTimer);
                    var term = $(this).val();
                    riderSearchTimer = setTimeout(function () {
                        renderRiderRows(filterRiders(term));
                    }, 150);
                });

                $(document).on('click', '#riderAssignTableBody .pds-dispatch-table-row', function () {
                    var rider = $(this).data('rider');
                    if (!rider || !rider.id) return;
                    submitReassign(rider.id);
                });

                function mountFollowUpDetailsActions() {
                    var $slot = $('#followUpDetailsHeaderActions');
                    var $src = $('#followUpDetailsModalBody').find('[data-fu-details-actions]');
                    $slot.empty();
                    if (!$src.length) return;
                    var $actions = $src.find('.pds-dispatch-row-actions').first();
                    if ($actions.length) {
                        $slot.append($actions);
                    }
                    $src.remove();
                }

                $(document).on('click', '.pds-follow-up-details-btn', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var url = $(this).data('url');
                    var $body = $('#followUpDetailsModalBody');
                    if (!url) return;
                    $('#followUpDetailsHeaderActions').empty();
                    $body.html('<p class="text-center py-4 mb-0">{{ __('message.splash_loading') }}</p>');
                    $('#followUpDetailsModal').modal('show');
                    $.get(url).done(function (html) {
                        $body.html(html);
                        mountFollowUpDetailsActions();
                    }).fail(function () {
                        $body.html('<p class="text-danger text-center py-4 mb-0">{{ __('message.something_went_wrong') }}</p>');
                    });
                });

                $('#followUpDetailsModal').on('hidden.bs.modal', function () {
                    $('#followUpDetailsHeaderActions').empty();
                    $('#followUpDetailsModalBody').empty();
                });

                $(document).on('click', '#followUpDetailsHeaderActions .loadRemoteModel, .pds-dispatch-action-edit.loadRemoteModel, .pds-dispatch-action-cancel.loadRemoteModel', function (e) {
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

                    if (!url || typeof $.confirm !== 'function') return;

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
                                            loadFollowUpPage(currentPage);
                                        },
                                        error: function (xhr) {
                                            var msg = (xhr.responseJSON && xhr.responseJSON.message)
                                                ? xhr.responseJSON.message
                                                : '{{ __('message.something_went_wrong') }}';
                                            if (typeof errorMessage === 'function') errorMessage(msg);
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

                loadFollowUpPage(1);
            })();
        </script>
    @endsection
</x-master-layout>
