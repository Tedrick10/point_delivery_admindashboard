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
        .pds-dispatch-items-datatable th:first-child,
        .pds-dispatch-items-datatable td:first-child {
            text-align: center;
            width: 42px;
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
            border: 1px solid #fdba74;
            background: #fff7ed;
            box-shadow: 0 6px 16px rgba(254, 111, 7, .12);
        }
        .pds-selected-item-value-box span {
            font-size: 11px;
            font-weight: 700;
            color: #c2410c;
            letter-spacing: .01em;
            line-height: 1.2;
        }
        .pds-selected-item-value-box strong {
            font-size: 18px;
            font-weight: 800;
            color: #9a3412;
            letter-spacing: 0;
            line-height: 1.2;
        }
        .pds-dispatch-items-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 0;
            padding: 14px 1.35rem 0;
        }
        .pds-dispatch-items-tab {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #334155;
            border-radius: 999px;
            padding: 8px 14px;
            font-weight: 700;
            text-decoration: none;
            transition: .15s ease;
        }
        .pds-dispatch-items-tab em {
            font-style: normal;
            min-width: 22px;
            height: 22px;
            padding: 0 6px;
            border-radius: 999px;
            background: #f1f5f9;
            color: #64748b;
            display: inline-grid;
            place-items: center;
            font-size: 12px;
        }
        .pds-dispatch-items-tab:hover { border-color: #fdba74; color: #c2410c; text-decoration: none; }
        .pds-dispatch-items-tab.is-active {
            background: linear-gradient(135deg, #FE6F07, #ff8f3d);
            border-color: transparent;
            color: #fff;
            box-shadow: 0 8px 18px rgba(254, 111, 7, .24);
        }
        .pds-dispatch-items-tab.is-active em { background: rgba(255,255,255,.22); color: #fff; }
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
                    <button type="button" class="pds-dispatch-items-icon-btn" title="{{ __('message.download') }}">
                        <i class="fas fa-cloud-download-alt"></i>
                    </button>
                    <button type="button" class="pds-dispatch-items-icon-btn" onclick="window.print()" title="{{ __('message.print') }}">
                        <i class="fas fa-print"></i>
                    </button>
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
            @endphp
            @if($destinationBranches->isNotEmpty())
                <div class="pds-dispatch-items-tabs" role="tablist" aria-label="{{ __('message.to') }}">
                    @foreach($destinationBranches as $branchTab)
                        @php $count = (int) ($tabCounts[$branchTab->id] ?? 0); @endphp
                        <a href="{{ route('order.dispatch.items', ['id' => $order->id, 'to_branch_id' => $branchTab->id] + (request('from') === 'dispatch' ? ['from' => 'dispatch'] : [])) }}"
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
                    {{ $dataTable->table(['class' => 'table w-100 pds-datatable pds-dispatch-items-datatable'], false) }}
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
        <script src="{{ asset('js/dispatch-item-form.js') }}?v=30"></script>
        <script src="{{ asset('js/admin-order-list-live.js') }}?v=3"></script>
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
                });

                setTimeout(function () {
                    $('#dataTableBuilder').trigger('draw.dt');
                }, 300);

                $(document).on('click', '.pds-dispatch-action-edit.loadRemoteModel, .pds-dispatch-photo-edit-btn.loadRemoteModel', function (e) {
                    e.stopPropagation();
                });

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
                    window.bootAdminOrderListLiveRefresh({
                        url: @json(route('order.dispatch.items.live-version', $order->id)),
                        intervalMs: 5000,
                        useItemsReload: true,
                        tableSelector: '.pds-dispatch-items-datatable'
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
