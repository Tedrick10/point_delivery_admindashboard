<x-master-layout :assets="$assets ?? []">
    @php
        $osName = resolveDispatchOsName($order);
        $canEditItemInfo = app(\App\Services\DispatchOrderWorkflowService::class)->canAdminEditDispatchItemInfo($order);
    @endphp

    <div class="container-fluid pds-page-wrap pds-motion-enter pds-dispatch-items-page">
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
                        <a href="{{ route('order.index') }}" class="pds-dispatch-items-btn-close" title="{{ __('message.close') }}">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </div>
            </div>

            @unless($canEditItemInfo)
                <div class="alert alert-warning mx-3 mt-3 mb-0" role="alert">
                    {{ __('message.dispatch_item_edit_requires_pickup_rider') }}
                </div>
            @endunless

            <div class="pds-dispatch-items-body">
                <div class="pds-dispatch-items-table-shell pds-table-shell">
                    {{ $dataTable->table(['class' => 'table w-100 pds-datatable pds-dispatch-items-datatable'], false) }}
                </div>
            </div>
        </div>
    </div>

    @include('order.partials._dispatch-photo-view-modal')
    @include('order.partials._dispatch-item-message-modal')

    @section('bottom_script')
        {{ $dataTable->scripts() }}
        <script src="{{ asset('js/dispatch-item-form.js') }}?v=26"></script>
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
                $(document).on('draw.dt', '#dataTableBuilder', function () {
                    var api = window.LaravelDataTables && window.LaravelDataTables['dataTableBuilder'];
                    if (!api) return;
                    var count = api.rows({ filter: 'applied' }).count();
                    $('.pds-dispatch-items-table-shell').toggleClass('is-empty', count === 0);
                    $('.pds-dispatch-items-screen').toggleClass('has-rows', count > 0);
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
    @endsection
</x-master-layout>
