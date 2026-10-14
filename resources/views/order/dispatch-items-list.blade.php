<x-master-layout :assets="$assets ?? []">
    @php
        $osName = resolveDispatchOsName($order);
        $canEditItemInfo = app(\App\Services\DispatchOrderWorkflowService::class)->canAdminEditDispatchItemInfo($order);
        $showKyoShinAction = $showKyoShinAction ?? false;
        $kyoShinActionUrl = $kyoShinActionUrl ?? route('order.dispatch.give-kyo-shin');
        $kyoShinClient = $order->relationLoaded('client') ? $order->client : $order->client()->first();
        $kyoShinSettlement = app(\App\Services\OsSettlementService::class);
        $kyoShinKpayName = $kyoShinClient ? $kyoShinSettlement->kpayNameFromUser($kyoShinClient) : '';
        $kyoShinKpayNo = $kyoShinClient ? $kyoShinSettlement->kpayNoFromUser($kyoShinClient) : '';
    @endphp

    <style>
        /* Viewport-locked screen: only the table body scrolls; orange thead + dock stay put. */
        body.pds-admin:has(.pds-dispatch-items-page),
        body.pds-admin:has(.pds-dispatch-items-page) #app {
            overflow: hidden !important;
            height: 100vh !important;
        }
        body.pds-admin:has(.pds-dispatch-items-page) .content-page {
            overflow: hidden !important;
            max-height: calc(100vh - var(--pds-topbar-h, 73px)) !important;
        }
        .pds-dispatch-items-page {
            display: flex !important;
            flex-direction: column !important;
            height: calc(100vh - var(--pds-topbar-h, 73px) - 1.25rem) !important;
            max-height: calc(100vh - var(--pds-topbar-h, 73px) - 1.25rem) !important;
            min-height: 0 !important;
            overflow: hidden !important;
        }
        .pds-dispatch-items-screen {
            display: flex !important;
            flex-direction: column !important;
            flex: 1 1 auto !important;
            height: 100% !important;
            min-height: 0 !important;
            max-height: 100% !important;
            overflow: hidden !important;
        }
        .pds-dispatch-items-topbar,
        .pds-dispatch-items-tabs,
        .pds-dispatch-items-page > .pds-dispatch-items-screen > .alert {
            flex: 0 0 auto !important;
        }
        .pds-dispatch-items-body {
            display: flex !important;
            flex-direction: column !important;
            flex: 1 1 auto !important;
            min-height: 0 !important;
            height: auto !important;
            padding-bottom: 0.85rem !important;
            padding-top: 0.5rem !important;
            overflow: hidden !important;
        }
        .pds-dispatch-items-table-shell {
            flex: 1 1 auto !important;
            min-height: 0 !important;
            height: auto !important;
            max-height: none !important;
            overflow-x: auto !important;
            overflow-y: auto !important;
            border-bottom-left-radius: 0 !important;
            border-bottom-right-radius: 0 !important;
            -webkit-overflow-scrolling: touch;
        }
        .pds-items-dock {
            flex: 0 0 auto !important;
            position: relative !important;
            z-index: 4 !important;
        }
        /* Kill empty DataTables chrome (sDom leftovers) that left a white slab under rows */
        .pds-dispatch-items-table-shell .dataTables_wrapper > .row,
        .pds-dispatch-items-table-shell .dataTables_wrapper > .d-flex,
        .pds-dispatch-items-table-shell .dataTables_wrapper > .clear,
        .pds-dispatch-items-table-shell .dataTables_wrapper .dt-buttons,
        .pds-dispatch-items-table-shell .dataTables_filter,
        .pds-dispatch-items-table-shell .dataTables_info,
        .pds-dispatch-items-table-shell .dataTables_length,
        .pds-dispatch-items-table-shell .dataTables_paginate {
            display: none !important;
            height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: hidden !important;
            border: 0 !important;
        }
        .pds-dispatch-items-table-shell .table-responsive.my-3,
        .pds-dispatch-items-table-shell .pds-dt-scroll-host {
            margin: 0 !important;
            padding: 0 !important;
            overflow: visible !important;
            height: auto !important;
            max-height: none !important;
        }
        .pds-dispatch-items-table-shell .dataTables_wrapper,
        .pds-dispatch-items-table-shell .dataTables_scroll,
        .pds-dispatch-items-table-shell .dataTables_scrollHead,
        .pds-dispatch-items-table-shell .dataTables_scrollBody,
        .pds-dispatch-items-table-shell .dataTables_scrollFoot {
            height: auto !important;
            max-height: none !important;
            min-height: 0 !important;
            overflow: visible !important;
        }
        .pds-dispatch-items-table-shell .dataTables_scrollFoot {
            display: none !important;
        }
        .pds-dispatch-items-table-shell .pds-dispatch-items-datatable,
        .pds-dispatch-items-table-shell table.pds-sticky-freeze {
            width: max-content !important;
            min-width: 100% !important;
            margin: 0 !important;
            border-collapse: separate !important;
            border-spacing: 0 !important;
            /* backend.css .table { overflow:hidden } breaks sticky thead */
            overflow: visible !important;
        }
        .pds-dispatch-items-datatable thead th,
        .pds-dispatch-items-table-shell table.pds-sticky-freeze thead th {
            position: -webkit-sticky !important;
            position: sticky !important;
            top: 0 !important;
            z-index: 8 !important;
            background: var(--site-color, #FE6F07) !important;
            color: #fff !important;
            box-shadow: 0 1px 0 #e07a28;
        }
        .pds-dispatch-items-datatable tbody td .pds-dispatch-photo-thumb-wrap {
            margin-top: 6px;
        }
        .pds-dispatch-items-datatable .pds-dispatch-photo-thumb-wrap,
        .pds-dispatch-items-datatable .pds-dispatch-photo-thumb-btn {
            width: 52px !important;
            height: 52px !important;
            min-width: 52px !important;
            min-height: 52px !important;
            flex-shrink: 0 !important;
        }
        .pds-dispatch-items-datatable td:has(.pds-dispatch-photo-thumb-wrap) {
            overflow: visible !important;
            padding-top: 0.85rem !important;
            padding-bottom: 0.7rem !important;
        }
        .pds-dispatch-items-datatable th:first-child,
        .pds-dispatch-items-datatable td:first-child {
            text-align: center;
            width: 42px !important;
            min-width: 42px !important;
            max-width: 48px !important;
            padding-left: 0.45rem !important;
            padding-right: 0.45rem !important;
        }
        .pds-dispatch-items-datatable th:nth-child(2),
        .pds-dispatch-items-datatable td:nth-child(2) {
            width: 40px !important;
            min-width: 40px !important;
            max-width: 48px !important;
            text-align: center;
            padding-left: 0.35rem !important;
            padding-right: 0.35rem !important;
        }
        .pds-dispatch-items-datatable thead th:first-child {
            pointer-events: auto;
        }
        .pds-dispatch-items-datatable .pds-dispatch-item-check-all,
        .pds-dispatch-items-datatable .pds-dispatch-item-check {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }
        .pds-selected-item-value-box {
            display: inline-flex;
            flex-direction: column;
            gap: 2px;
            min-width: 168px;
            padding: 8px 14px 9px;
            border-radius: 12px;
            border: 1px solid rgba(var(--brand-rgb), 0.45);
            background: rgba(var(--brand-rgb), 0.08);
            box-shadow: 0 6px 16px rgba(var(--brand-rgb), .12);
        }
        .pds-selected-item-value-box span {
            font-size: 11px;
            font-weight: 700;
            color: var(--site-color);
            letter-spacing: .01em;
            line-height: 1.2;
        }
        .pds-selected-item-value-box strong {
            font-size: 18px;
            font-weight: 800;
            color: var(--site-color);
            letter-spacing: 0;
            line-height: 1.2;
        }
        .pds-dispatch-items-page .pds-dispatch-items-tabs {
            margin: 0 1.35rem 0.85rem;
            width: auto;
        }
        .pds-dispatch-items-datatable thead th.sorting,
        .pds-dispatch-items-datatable thead th.sorting_asc,
        .pds-dispatch-items-datatable thead th.sorting_desc,
        .pds-dispatch-items-datatable thead th.sorting_asc_disabled,
        .pds-dispatch-items-datatable thead th.sorting_desc_disabled {
            background-image: none !important;
            cursor: default !important;
            padding-right: 10px !important;
        }
        .pds-dispatch-items-datatable thead th.sorting:before,
        .pds-dispatch-items-datatable thead th.sorting:after,
        .pds-dispatch-items-datatable thead th.sorting_asc:before,
        .pds-dispatch-items-datatable thead th.sorting_asc:after,
        .pds-dispatch-items-datatable thead th.sorting_desc:before,
        .pds-dispatch-items-datatable thead th.sorting_desc:after {
            display: none !important;
            content: none !important;
        }
        .pds-items-dock {
            display: flex;
            align-items: stretch;
            justify-content: space-between;
            gap: 1rem;
            flex: 0 0 auto !important;
            flex-wrap: wrap;
            padding: 0.85rem 1rem;
            background:
                linear-gradient(180deg, #fffdfb 0%, #fff7ed 100%);
            border: 1px solid #f0d9c4;
            border-top: 0;
            border-radius: 0 0 14px 14px;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8);
            position: relative;
            z-index: 4;
        }
        .pds-items-dock__meta {
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 0.15rem;
            min-width: 8rem;
        }
        .pds-items-dock__kicker {
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--site-color, #FE6F07);
        }
        .pds-items-dock__count {
            font-size: 0.92rem;
            font-weight: 700;
            color: #334155;
        }
        .pds-items-dock__metrics {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-left: auto;
        }
        .pds-items-metric {
            min-width: 7.25rem;
            padding: 0.55rem 0.8rem 0.6rem;
            border-radius: 12px;
            background: #fff;
            border: 1px solid #f3e4d6;
            box-shadow: 0 1px 2px rgba(154, 52, 18, 0.04);
        }
        .pds-items-metric span {
            display: block;
            font-size: 0.62rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #94a3b8;
            margin-bottom: 0.2rem;
        }
        .pds-items-metric strong {
            display: block;
            font-size: 1.02rem;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
            color: #0f172a;
            line-height: 1.2;
        }
        .pds-items-metric--pay {
            background: #fff7ed;
            border-color: rgba(var(--brand-rgb), 0.35);
        }
        .pds-items-metric--pay strong {
            color: var(--site-color, #FE6F07);
        }
        .pds-items-metric.is-negative {
            background: #fef2f2;
            border-color: #fecaca;
        }
        .pds-items-metric.is-negative strong {
            color: #dc2626;
        }
        /* Kill DT footer row — was the empty white/peach bar under the last item */
        .pds-dispatch-items-table-shell tfoot,
        .pds-dispatch-items-datatable tfoot,
        .pds-dispatch-items-table-shell .dataTables_scrollFoot,
        .pds-dispatch-items-dt-footer {
            display: none !important;
            height: 0 !important;
            max-height: 0 !important;
            min-height: 0 !important;
            overflow: hidden !important;
            border: 0 !important;
            padding: 0 !important;
            margin: 0 !important;
            visibility: hidden !important;
            pointer-events: none !important;
        }
        .pds-dispatch-items-table-shell tfoot th,
        .pds-dispatch-items-table-shell tfoot td,
        .pds-dispatch-items-datatable tfoot th,
        .pds-dispatch-items-datatable tfoot td {
            display: none !important;
            height: 0 !important;
            padding: 0 !important;
            border: 0 !important;
            line-height: 0 !important;
            font-size: 0 !important;
        }
    </style>
    <div class="container-fluid pds-page-wrap pds-motion-enter pds-dispatch-items-page" data-active-to-branch="{{ (int) ($activeToBranchId ?? 0) }}">
        <div class="pds-dispatch-items-screen">
            <div class="pds-dispatch-items-topbar">
                <div class="pds-dispatch-items-topbar-main">
                    <div class="pds-dispatch-items-topbar-copy">
                        <span class="pds-dispatch-items-badge">{{ __('message.order') }} #{{ $order->id }}</span>
                        <h4 class="pds-dispatch-items-heading">{{ __('message.order_detail_list') }}</h4>
                        <p class="pds-dispatch-items-subtitle">{{ __('message.os_name') }}: <strong>{{ $osName }}</strong></p>
                    </div>
                </div>
                <div class="pds-dispatch-items-topbar-actions">
                    <div class="pds-selected-item-value-box" id="selectedItemValueBox" aria-live="polite">
                        <span>{{ __('message.item_value') }} {{ __('message.total_amount') }}</span>
                        <strong id="selectedItemValueTotal">0</strong>
                    </div>
                    @if($showKyoShinAction && auth()->user()->can('order-edit'))
                        <button type="button" class="pds-assign-action-btn pds-assign-action-btn--kyo-shin" id="giveKyoShinBtn" disabled>
                            <span class="pds-assign-action-btn__icon" aria-hidden="true">
                                <i class="fas fa-hand-holding-usd"></i>
                            </span>
                            <span class="pds-assign-action-btn__label">{{ __('message.kyo_shin_give') }}</span>
                        </button>
                    @endif
                    <a href="{{ route('order.dispatch.items.export', ['id' => $order->id, 'to_branch_id' => $activeToBranchId ?? 0]) }}"
                       class="pds-dispatch-items-icon-btn"
                       id="dispatchItemsDownloadBtn"
                       title="{{ __('message.download') }}">
                        <i class="fas fa-cloud-download-alt"></i>
                    </a>
                    <a href="{{ route('order.dispatch.items.print', ['id' => $order->id, 'to_branch_id' => $activeToBranchId ?? 0, 'autoprint' => 1]) }}"
                       class="pds-dispatch-items-icon-btn"
                       id="dispatchItemsPrintBtn"
                       target="_blank"
                       rel="noopener"
                       title="{{ __('message.print') }}">
                        <i class="fas fa-print"></i>
                    </a>
                    @if(auth()->user()->can('order-add') && $canEditItemInfo)
                        <a href="{{ route('order.dispatch.item.create', $order->id) }}"
                           class="pds-dispatch-items-btn-add loadRemoteModel"
                           title="{{ __('message.add_or_update_item') }}">
                            <i class="fas fa-plus"></i>
                            <span>{{ __('message.add') }}</span>
                        </a>
                    @endif
                    @if(request('from') === 'dispatch')
                        <a href="{{ route('order.create', ['order_id' => $order->id]) }}" class="pds-dispatch-items-btn-close" title="{{ __('message.back_to_add_order') }}">
                            <i class="fas fa-times"></i>
                        </a>
                    @else
                        @php
                            $closeQuery = array_filter([
                                'dispatch_status' => request('dispatch_status') ?: null,
                            ]);
                        @endphp
                        <a href="{{ route('order.index', $closeQuery) }}" class="pds-dispatch-items-btn-close" title="{{ __('message.close') }}">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </div>
            </div>

            @unless($canEditItemInfo)
                @if(empty($order->delivery_man_id) && ! $showKyoShinAction)
                    <div class="alert alert-warning mx-3 mt-3 mb-0" role="alert">
                        {{ __('message.dispatch_item_edit_requires_pickup_rider') }}
                    </div>
                @endif
            @endunless

            @php
                $destinationBranches = $destinationBranches ?? collect();
                $activeToBranchId = (int) ($activeToBranchId ?? 0);
                $tabCounts = $tabCounts ?? collect();
                $itemsQueryExtras = array_filter([
                    'from' => request('from') === 'dispatch' ? 'dispatch' : null,
                    'dispatch_status' => request('dispatch_status') ?: null,
                ]);
            @endphp
            @if($destinationBranches->isNotEmpty())
                <div class="pds-dispatch-items-tabs" role="tablist" aria-label="{{ __('message.to') }}">
                    @foreach($destinationBranches as $branchTab)
                        @php $count = (int) ($tabCounts[$branchTab->id] ?? 0); @endphp
                        <a href="{{ route('order.dispatch.items', ['id' => $order->id, 'to_branch_id' => $branchTab->id] + $itemsQueryExtras) }}"
                           class="pds-dispatch-items-tab{{ $activeToBranchId === (int) $branchTab->id ? ' is-active' : '' }}"
                           role="tab">
                            <span>{{ $branchTab->name }}</span>
                            <em>{{ $count }}</em>
                        </a>
                    @endforeach
                </div>
            @endif

            <div class="pds-dispatch-items-body">
                <div class="pds-dispatch-items-table-shell pds-table-shell">
                    {{ $dataTable->table(['class' => 'table w-100 pds-datatable pds-dispatch-items-datatable pds-sticky-freeze'], false) }}
                </div>
                <div class="pds-items-dock" id="dispatchItemsDock">
                    <div class="pds-items-dock__meta">
                        <span class="pds-items-dock__kicker">{{ __('message.total_amount') }}</span>
                        <span class="pds-items-dock__count" id="dispatchItemsCount">0 {{ __('message.item_count') }}</span>
                    </div>
                    <div class="pds-items-dock__metrics">
                        <div class="pds-items-metric">
                            <span>{{ __('message.advance_paid') }}</span>
                            <strong data-total-key="advance_paid">0</strong>
                        </div>
                        <div class="pds-items-metric">
                            <span>{{ __('message.item_value') }}</span>
                            <strong data-total-key="item_value">0</strong>
                        </div>
                        <div class="pds-items-metric">
                            <span>{{ __('message.deli_amount') }}</span>
                            <strong data-total-key="deli_amount">0</strong>
                        </div>
                        <div class="pds-items-metric">
                            <span>{{ __('message.cust_get') }}</span>
                            <strong data-total-key="cust_get">0</strong>
                        </div>
                        <div class="pds-items-metric pds-items-metric--pay">
                            <span>{{ __('message.os_to_pay') }}</span>
                            <strong data-total-key="os_to_pay">0</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('order.partials._dispatch-photo-view-modal')
    @include('order.partials._dispatch-item-message-modal')
    @if($showKyoShinAction && auth()->user()->can('order-edit'))
        @include('order.partials._kyo-shin-give-modal', [
            'kyoShinKpayName' => $kyoShinKpayName,
            'kyoShinKpayNo' => $kyoShinKpayNo,
        ])
    @endif

    @section('bottom_script')
        {{ $dataTable->scripts() }}
        <script src="{{ asset('js/pds-photo-zoom.js') }}?v=3"></script>
        <script src="{{ asset('js/dispatch-item-form.js') }}?v=37"></script>
        {{-- admin-live.js is loaded globally from _scripts --}}
        <script>
            (function () {
                if (typeof Snackbar !== 'undefined' && typeof Snackbar.close === 'function') {
                    Snackbar.close();
                }

                window.reloadDispatchItemsTable = function () {
                    var dt = window.LaravelDataTables && window.LaravelDataTables['dataTableBuilder'];
                    if (dt) dt.ajax.reload(null, false);
                };
            })();
        </script>
        @include('order.partials._dispatch-item-message-scripts')
        <script>
            (function () {
                function formatAmount(value) {
                    var amount = Math.round(Number(value) || 0);
                    return amount.toLocaleString('en-US');
                }

                window.pdsFitDispatchItemsScroll = function (dt) {
                    // Shell must be the scrollport; sticky orange thead sticks to shell top.
                    var api = (dt && typeof dt.rows === 'function')
                        ? dt
                        : (dt && dt.api ? dt.api() : (window.LaravelDataTables && window.LaravelDataTables['dataTableBuilder']));
                    var $shell = $('.pds-dispatch-items-table-shell').first();
                    if (api && api.table) {
                        $shell = $(api.table().container()).closest('.pds-dispatch-items-table-shell');
                    }
                    if (!$shell.length) return;
                    var shell = $shell[0];
                    $shell.removeClass('pds-plain-freeze').addClass('pds-plain-freeze-skip');
                    shell.style.setProperty('min-height', '0', 'important');
                    shell.style.setProperty('overflow-y', 'auto', 'important');
                    shell.style.setProperty('overflow-x', 'auto', 'important');
                    shell.style.setProperty('flex', '1 1 auto', 'important');
                    // Explicit pixel height so overflow-y:auto actually scrolls (flex alone can fail).
                    var dock = document.getElementById('dispatchItemsDock');
                    var top = shell.getBoundingClientRect().top;
                    var dockH = dock ? dock.getBoundingClientRect().height : 0;
                    var avail = Math.floor(window.innerHeight - top - dockH - 12);
                    if (avail > 160) {
                        shell.style.setProperty('height', avail + 'px', 'important');
                        shell.style.setProperty('max-height', avail + 'px', 'important');
                    }
                    $shell.find('.dataTables_scroll, .dataTables_scrollBody, .dataTables_scrollHead').each(function () {
                        this.style.setProperty('height', 'auto', 'important');
                        this.style.setProperty('max-height', 'none', 'important');
                        this.style.setProperty('min-height', '0', 'important');
                        this.style.setProperty('overflow', 'visible', 'important');
                    });
                    $shell.find('.dataTables_scrollFoot, tfoot').remove();
                    $shell.find('table.pds-dispatch-items-datatable, table.pds-sticky-freeze').each(function () {
                        this.style.setProperty('overflow', 'visible', 'important');
                        this.style.setProperty('border-collapse', 'separate', 'important');
                    });
                    $shell.find('thead th').each(function () {
                        this.style.setProperty('position', 'sticky', 'important');
                        this.style.setProperty('top', '0', 'important');
                        this.style.setProperty('z-index', '8', 'important');
                        this.style.setProperty('background', getComputedStyle(document.body).getPropertyValue('--site-color').trim() || '#FE6F07', 'important');
                        this.style.setProperty('color', '#fff', 'important');
                    });
                };

                window.pdsFillDispatchItemsTotals = function (dt) {
                    var api = (dt && typeof dt.rows === 'function')
                        ? dt
                        : (dt && dt.api ? dt.api() : (window.LaravelDataTables && window.LaravelDataTables['dataTableBuilder']));
                    if (!api || !api.columns) return;

                    function colIdx(name) {
                        var found = -1;
                        api.columns().every(function (index) {
                            if (this.dataSrc() === name) {
                                found = index;
                            }
                        });
                        return found;
                    }

                    var json = typeof api.ajax.json === 'function' ? (api.ajax.json() || {}) : {};
                    var totals = json.pds_totals || {};
                    var keys = ['advance_paid', 'item_value', 'deli_amount', 'cust_get', 'os_to_pay'];

                    keys.forEach(function (key) {
                        if (typeof totals[key] === 'number') return;
                        var idx = colIdx(key);
                        if (idx < 0) return;
                        var sum = 0;
                        api.column(idx, { page: 'all' }).nodes().to$().each(function () {
                            var raw = $(this).find('[data-amount]').attr('data-amount');
                            if (raw == null) {
                                raw = String($(this).text()).replace(/,/g, '');
                            }
                            sum += parseFloat(raw) || 0;
                        });
                        totals[key] = sum;
                    });

                    $('#dispatchItemsDock [data-total-key]').each(function () {
                        var key = this.getAttribute('data-total-key');
                        var value = Number(totals[key] || 0);
                        $(this).text(formatAmount(value));
                        $(this).closest('.pds-items-metric').toggleClass('is-negative', key === 'os_to_pay' && value < 0);
                    });

                    var count = api.rows({ filter: 'applied' }).count();
                    $('#dispatchItemsCount').text(count + ' ' + @json(__('message.item_count')));

                    var $table = $(api.table().node());
                    $table.find('tfoot').remove();
                    $table.closest('.dataTables_wrapper').find('.dataTables_scrollFoot, tfoot').remove();
                };

                function ensureDispatchItemsSelectAll() {
                    var $th = $('#dataTableBuilder thead th:first');
                    if ($th.length && !$th.find('#dispatchItemsSelectAll').length) {
                        $th.html('<input type="checkbox" id="dispatchItemsSelectAll" class="pds-dispatch-item-check-all" title="{{ e(__('message.select_all')) }}">');
                    }
                }

                function formatSelectedAmount(value) {
                    var amount = Math.round(Number(value) || 0);
                    return amount.toLocaleString('en-US');
                }

                function updateSelectedItemValueBox() {
                    var total = 0;
                    $('#dataTableBuilder tbody .pds-dispatch-item-check:checked').each(function () {
                        total += parseFloat($(this).data('item-value')) || 0;
                    });
                    $('#selectedItemValueTotal').text(formatSelectedAmount(total));
                }

                function syncDispatchItemsSelectAll() {
                    ensureDispatchItemsSelectAll();
                    var $checks = $('#dataTableBuilder tbody .pds-dispatch-item-check');
                    var $all = $('#dispatchItemsSelectAll');
                    if ($all.length) {
                        $all.prop('checked', $checks.length > 0 && $checks.filter(':checked').length === $checks.length);
                    }
                    updateSelectedItemValueBox();
                }

                $(document).on('change', '#dispatchItemsSelectAll', function () {
                    var isChecked = $(this).is(':checked');
                    $('#dataTableBuilder tbody .pds-dispatch-item-check').prop('checked', isChecked).trigger('change');
                });

                $(document).on('change', '#dataTableBuilder tbody .pds-dispatch-item-check', syncDispatchItemsSelectAll);

                $(document).on('draw.dt', '#dataTableBuilder', function () {
                    var api = window.LaravelDataTables && window.LaravelDataTables['dataTableBuilder'];
                    if (!api) return;
                    var count = api.rows({ filter: 'applied' }).count();
                    $('.pds-dispatch-items-table-shell').toggleClass('is-empty', count === 0);
                    $('.pds-dispatch-items-screen').toggleClass('has-rows', count > 0);
                    syncDispatchItemsSelectAll();
                    window.pdsFillDispatchItemsTotals(api);
                    window.pdsFitDispatchItemsScroll(api);
                });

                var fitTimer = null;
                function scheduleFit() {
                    if (fitTimer) clearTimeout(fitTimer);
                    fitTimer = setTimeout(function () {
                        var api = window.LaravelDataTables && window.LaravelDataTables['dataTableBuilder'];
                        if (api) window.pdsFitDispatchItemsScroll(api);
                    }, 80);
                }
                $(window).on('resize.pdsDispatchItemsScroll', scheduleFit);

                setTimeout(function () {
                    $('#dataTableBuilder').trigger('draw.dt');
                    scheduleFit();
                }, 300);

                $(document).on('click', '.pds-dispatch-action-edit.loadRemoteModel, .pds-dispatch-photo-edit-btn.loadRemoteModel', function (e) {
                    e.stopPropagation();
                });

                @if(! empty($riderDonePresence))
                (function bootRiderDonePresence() {
                    var cfg = @json($riderDonePresence);
                    if (!cfg || !cfg.token || !cfg.heartbeat_url || !cfg.release_url) return;
                    window.__pdsRiderDoneItemsLock = true;
                    var released = false;
                    var timer = null;

                    function csrf() {
                        return $('meta[name="csrf-token"]').attr('content') || '';
                    }

                    function release() {
                        if (released) return;
                        released = true;
                        window.__pdsRiderDoneItemsLock = false;
                        if (timer) clearInterval(timer);
                        var body = new FormData();
                        body.append('_token', csrf());
                        body.append('token', cfg.token);
                        body.append('order_id', String(cfg.order_id || {{ (int) $order->id }}));
                        if (navigator.sendBeacon) {
                            navigator.sendBeacon(cfg.release_url, body);
                            return;
                        }
                        $.ajax({
                            url: cfg.release_url,
                            method: 'POST',
                            data: {
                                _token: csrf(),
                                token: cfg.token,
                                order_id: cfg.order_id || {{ (int) $order->id }},
                            },
                            async: false,
                        });
                    }

                    function heartbeat() {
                        if (released) return;
                        $.ajax({
                            url: cfg.heartbeat_url,
                            method: 'POST',
                            data: {
                                _token: csrf(),
                                token: cfg.token,
                                order_id: cfg.order_id || {{ (int) $order->id }},
                            },
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                            error: function (xhr) {
                                if (xhr && xhr.status === 423) {
                                    var msg = (xhr.responseJSON && xhr.responseJSON.message)
                                        ? xhr.responseJSON.message
                                        : @json(__('message.admin_rider_done_busy', ['name' => __('message.admin')]));
                                    if (window.Swal) {
                                        Swal.fire({
                                            icon: 'warning',
                                            title: @json(__('message.admin_rider_done_busy_title')),
                                            text: msg,
                                            confirmButtonColor: (window.pdsBrandColor || '#FE6F07'),
                                        }).then(function () {
                                            window.location.href = @json(route('order.index', ['orders_type' => 'list', 'dispatch_status' => 'rider_pick_up_done']));
                                        });
                                    } else {
                                        alert(msg);
                                        window.location.href = @json(route('order.index', ['orders_type' => 'list', 'dispatch_status' => 'rider_pick_up_done']));
                                    }
                                }
                            },
                        });
                    }

                    timer = setInterval(heartbeat, 25000);
                    $(window).on('beforeunload.riderDonePresence pagehide.riderDonePresence', release);
                    $(document).on('click.riderDonePresence', '.pds-dispatch-items-btn-close', function () {
                        release();
                    });
                })();
                @endif


                if (typeof window.initDispatchItemForm === 'function') {
                    window.initDispatchItemForm({
                        nrcDataUrl: "{{ asset('data/myanmar-nrc.json') }}",
                        townshipsUrl: "{{ route('delivery-route-locations.townships') }}",
                        citiesStoreUrl: "{{ route('delivery-route-locations.cities.store') }}",
                        townshipsStoreUrl: "{{ route('delivery-route-locations.townships.store') }}",
                        branchesStoreUrl: "{{ route('delivery-route-locations.branches.store') }}",
                        modalParent: '#remoteModelData'
                    });
                }

                if (typeof window.bootAdminOrderListLiveRefresh === 'function') {
                    window.__pdsPageLiveBooted = true;
                    window.bootAdminOrderListLiveRefresh({
                        url: @json(route('order.dispatch.items.live-version', $order->id)),
                        intervalMs: 5000,
                        useItemsReload: true,
                        tableSelector: '.pds-dispatch-items-datatable',
                        data: { order_id: {{ (int) $order->id }} }
                    });
                }

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
                                            if (typeof window.reloadDispatchItemsTable === 'function') {
                                                window.reloadDispatchItemsTable();
                                            } else if (window.LaravelDataTables && window.LaravelDataTables['dataTableBuilder']) {
                                                window.LaravelDataTables['dataTableBuilder'].ajax.reload(null, false);
                                            }
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
        @if($showKyoShinAction && auth()->user()->can('order-edit'))
            @include('order.partials._kyo-shin-give-scripts', [
                'kyoShinActionUrl' => $kyoShinActionUrl,
                'kyoShinOrderId' => $order->id,
            ])
        @endif
    @endsection
</x-master-layout>
