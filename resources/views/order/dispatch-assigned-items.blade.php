<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter pds-dispatch-to-assign-page pds-dispatch-assigned-items-page">
        <div class="pds-dispatch-to-assign-screen">
            <div class="pds-dispatch-to-assign-topbar">
                <div class="pds-dispatch-to-assign-topbar-copy">
                    <h4 class="pds-dispatch-to-assign-heading">{{ $pageTitle ?? __('message.assigned_item_list') }}</h4>
                    <p class="pds-dispatch-to-assign-subtitle">
                        {{ __('message.assigned_item_list_subtitle') }}
                        · {{ __('message.item_count') }} = <strong id="assignedItemCount">{{ $items->count() }}</strong>
                    </p>
                </div>
                <div class="pds-dispatch-to-assign-topbar-actions">
                    <a href="{{ route('order.dispatch.assign-100') }}" class="pds-assign-action-btn pds-assign-action-btn--rider" title="{{ __('message.assign_100') }}">
                        <span class="pds-assign-action-btn__icon" aria-hidden="true">
                            <i class="fas fa-users-cog"></i>
                        </span>
                        <span class="pds-assign-action-btn__label">{{ __('message.assign_100') }}</span>
                    </a>
                </div>
            </div>

            <div class="pds-dispatch-to-assign-filter">
                @php $tabCounts = $tabCounts ?? ['unread' => 0, 'unanswered' => 0, 'answered' => 0]; @endphp
                <div class="pds-msg-tabs" id="assignedMsgTabs" role="tablist">
                    <button type="button" class="pds-msg-tabs__btn is-active" data-msg-tab="unread">
                        {{ __('message.message_tab_unread') }}
                        <span class="pds-msg-tabs__count" data-count-for="unread">{{ $tabCounts['unread'] }}</span>
                    </button>
                    <button type="button" class="pds-msg-tabs__btn" data-msg-tab="unanswered">
                        {{ __('message.message_tab_unanswered') }}
                        <span class="pds-msg-tabs__count" data-count-for="unanswered">{{ $tabCounts['unanswered'] }}</span>
                    </button>
                    <button type="button" class="pds-msg-tabs__btn" data-msg-tab="answered">
                        {{ __('message.message_tab_answered') }}
                        <span class="pds-msg-tabs__count" data-count-for="answered">{{ $tabCounts['answered'] }}</span>
                    </button>
                </div>
                <div class="pds-dispatch-to-assign-filter-grid pds-dispatch-assign-100-filter-grid">
                    <div class="pds-dispatch-field pds-dispatch-field-sm">
                        <label for="assigned_rider_filter">{{ __('message.delivery_man') }}</label>
                        <input type="text" id="assigned_rider_filter" class="pds-dispatch-input" placeholder="{{ __('message.delivery_man') }}" autocomplete="off">
                    </div>
                    <div class="pds-dispatch-to-assign-os-filter">
                        <div class="pds-dispatch-field pds-dispatch-field-sm">
                            <label for="assigned_os_name">{{ __('message.os_name') }}</label>
                            <input type="text" id="assigned_os_name" class="pds-dispatch-input" placeholder="{{ __('message.os_name') }}" autocomplete="off">
                        </div>
                    </div>
                    <div class="pds-dispatch-field pds-dispatch-field-sm">
                        <label for="assigned_customer_search">{{ __('message.customer_name') }} / {{ __('message.phone') }}</label>
                        <input type="text" id="assigned_customer_search" class="pds-dispatch-input" placeholder="{{ __('message.customer_name') }} / {{ __('message.phone') }}" autocomplete="off">
                    </div>
                </div>
            </div>

            <div class="pds-dispatch-to-assign-body">
                @if($items->isEmpty())
                    <div class="pds-dispatch-to-assign-empty" id="assignedEmptyState">
                        <i class="fas fa-inbox"></i>
                        <p>{{ __('message.no_record_found') }}</p>
                    </div>
                @else
                    <div class="pds-dispatch-to-assign-empty d-none" id="assignedFilterEmptyState">
                        <i class="fas fa-search"></i>
                        <p>{{ __('message.no_record_found') }}</p>
                    </div>
                    <div class="pds-msg-thread-list" id="assignedTableShell">
                        @foreach($items as $item)
                            @php
                                $order = $item->order;
                                $osName = resolveDispatchOsName($order);
                                $deliveryRider = optional($item->deliveryMan)->name ?? '-';
                                $parcelId = $item->code ?: ('#'.$item->id);
                                $customerName = trim((string) ($item->customer_name ?? ''));
                                $customerPhone = trim((string) ($item->customer_phone ?? ''));
                                $hasCustomer = $customerName !== '' || $customerPhone !== '';
                                $senderLabel = $item->last_chat_sender_label ?: 'Admin';
                                $preview = $item->last_chat_message ?: '—';
                                $unread = (int) ($item->unreplied_count ?? 0);
                                $needsReply = ! empty($item->needs_reply) ? 1 : 0;
                                $time = $item->last_chat_at
                                    ? \Carbon\Carbon::parse($item->last_chat_at)->timezone('Asia/Yangon')->format('d-m H:i')
                                    : '';
                                $initial = $hasCustomer
                                    ? mb_strtoupper(mb_substr($customerName !== '' ? $customerName : $customerPhone, 0, 1))
                                    : mb_strtoupper(mb_substr((string) $parcelId, 0, 1));
                            @endphp
                            <div
                                class="pds-msg-thread-card {{ $unread > 0 ? 'is-unread' : '' }} {{ $needsReply ? 'is-unanswered' : 'is-answered' }}"
                                data-os-name="{{ $osName !== '-' ? $osName : '' }}"
                                data-rider-name="{{ $deliveryRider !== '-' ? $deliveryRider : '' }}"
                                data-customer-name="{{ $customerName }}"
                                data-customer-phone="{{ $customerPhone }}"
                                data-item-id="{{ $item->id }}"
                                data-unread="{{ $unread > 0 ? 1 : 0 }}"
                                data-unanswered="{{ $needsReply }}"
                                data-answered="{{ $needsReply ? 0 : 1 }}"
                            >
                                <div class="pds-msg-thread-card__avatar" aria-hidden="true">{{ $initial }}</div>
                                <div class="pds-msg-thread-card__body">
                                    <div class="pds-msg-thread-card__line1">
                                        <strong class="pds-msg-thread-card__parcel">{{ $parcelId }}</strong>
                                        @if($time !== '')
                                            <span class="pds-msg-thread-card__time">{{ $time }}</span>
                                        @endif
                                    </div>
                                    @if($hasCustomer)
                                        <div class="pds-msg-thread-card__line2">
                                            @if($customerName !== '' && $customerPhone !== '')
                                                {{ $customerName }} · {{ $customerPhone }}
                                            @elseif($customerName !== '')
                                                {{ $customerName }}
                                            @else
                                                {{ $customerPhone }}
                                            @endif
                                        </div>
                                    @endif
                                    <div class="pds-msg-thread-card__line3">
                                        <span class="pds-msg-thread-card__sender">({{ $senderLabel }})</span>
                                        {{ $preview }}
                                    </div>
                                </div>
                                <div class="pds-msg-thread-card__aside">
                                    @if($unread > 0)
                                        <span class="pds-msg-thread-card__badge">{{ $unread > 99 ? '99+' : $unread }}</span>
                                    @endif
                                    @include('order.dispatch-item-action', ['item' => $item, 'hideGate' => true])
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    @include('order.partials._dispatch-item-message-modal')

    @section('bottom_script')
        <style>
            .pds-msg-thread-list {
                display: flex;
                flex-direction: column;
                gap: 10px;
                padding: 0 0 1.25rem;
            }
            .pds-msg-thread-card {
                display: flex;
                align-items: flex-start;
                gap: 12px;
                padding: 14px 16px;
                border-radius: 14px;
                border: 1px solid #e2e8f0;
                background: #fff;
                transition: border-color 0.15s ease, box-shadow 0.15s ease;
            }
            .pds-msg-thread-card:hover {
                border-color: #fdba74;
                box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
            }
            .pds-msg-thread-card.is-unread {
                background: linear-gradient(90deg, #fff7ed 0%, #fff 55%);
                border-color: #fed7aa;
            }
            .pds-msg-thread-card__avatar {
                width: 42px;
                height: 42px;
                border-radius: 999px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                background: linear-gradient(135deg, #fb923c 0%, #ea580c 100%);
                color: #fff;
                font-weight: 800;
            }
            .pds-msg-thread-card__body { flex: 1; min-width: 0; }
            .pds-msg-thread-card__line1 {
                display: flex;
                align-items: baseline;
                justify-content: space-between;
                gap: 10px;
            }
            .pds-msg-thread-card__parcel {
                color: #0f172a;
                font-size: 0.95rem;
            }
            .pds-msg-thread-card__time {
                color: #94a3b8;
                font-size: 0.75rem;
                flex-shrink: 0;
            }
            .pds-msg-thread-card__line2 {
                margin-top: 3px;
                color: #475569;
                font-size: 0.84rem;
                font-weight: 600;
            }
            .pds-msg-thread-card__line3 {
                margin-top: 4px;
                color: #334155;
                font-size: 0.86rem;
                line-height: 1.35;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }
            .pds-msg-thread-card.is-unread .pds-msg-thread-card__line3 {
                font-weight: 700;
                color: #0f172a;
            }
            .pds-msg-thread-card__sender {
                color: #ea580c;
                font-weight: 800;
                margin-right: 4px;
            }
            .pds-msg-thread-card__aside {
                display: flex;
                flex-direction: column;
                align-items: flex-end;
                gap: 8px;
                flex-shrink: 0;
            }
            .pds-msg-thread-card__badge {
                display: inline-flex;
                min-width: 22px;
                height: 22px;
                padding: 0 7px;
                align-items: center;
                justify-content: center;
                border-radius: 999px;
                background: #ea580c;
                color: #fff;
                font-size: 0.72rem;
                font-weight: 800;
            }
            .pds-msg-tabs {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                margin-bottom: 14px;
            }
            .pds-msg-tabs__btn {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                border: 1px solid #e2e8f0;
                background: #fff;
                color: #475569;
                border-radius: 999px;
                padding: 7px 14px;
                font-size: 0.84rem;
                font-weight: 700;
                cursor: pointer;
                transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
            }
            .pds-msg-tabs__btn:hover {
                border-color: #fdba74;
                color: #c2410c;
            }
            .pds-msg-tabs__btn.is-active {
                background: #ea580c;
                border-color: #ea580c;
                color: #fff;
            }
            .pds-msg-tabs__count {
                display: inline-flex;
                min-width: 20px;
                height: 20px;
                padding: 0 6px;
                align-items: center;
                justify-content: center;
                border-radius: 999px;
                background: rgba(15, 23, 42, 0.08);
                font-size: 0.72rem;
                font-weight: 800;
            }
            .pds-msg-tabs__btn.is-active .pds-msg-tabs__count {
                background: rgba(255, 255, 255, 0.22);
            }
        </style>
        @include('order.partials._dispatch-item-message-scripts')
        <script>
            $(document).ready(function () {
                window.reloadDispatchItemsTable = function () {
                    window.location.reload();
                };

                var allowedMsgTabs = ['unread', 'unanswered', 'answered'];
                var savedMsgTab = '';
                try { savedMsgTab = String(sessionStorage.getItem('pdsMsgTab') || ''); } catch (e) {}
                var activeMsgTab = allowedMsgTabs.indexOf(savedMsgTab) !== -1 ? savedMsgTab : 'unread';

                function applyAssignedFilters() {
                    var rider = String($('#assigned_rider_filter').val() || '').toLowerCase().trim();
                    var osName = String($('#assigned_os_name').val() || '').toLowerCase().trim();
                    var customer = String($('#assigned_customer_search').val() || '').toLowerCase().trim();
                    var visible = 0;

                    $('#assignedTableShell .pds-msg-thread-card').each(function () {
                        var $row = $(this);
                        var rowRider = String($row.data('rider-name') || '').toLowerCase();
                        var rowOs = String($row.data('os-name') || '').toLowerCase();
                        var rowCustomer = String($row.data('customer-name') || '').toLowerCase();
                        var rowPhone = String($row.data('customer-phone') || '').toLowerCase();
                        var isUnread = String($row.data('unread') || '0') === '1';
                        var isUnanswered = String($row.data('unanswered') || '0') === '1';
                        var isAnswered = String($row.data('answered') || '0') === '1';
                        var matchRider = !rider || rowRider.indexOf(rider) !== -1;
                        var matchOs = !osName || rowOs.indexOf(osName) !== -1;
                        var matchCustomer = !customer
                            || rowCustomer.indexOf(customer) !== -1
                            || rowPhone.indexOf(customer) !== -1;
                        var matchTab = (activeMsgTab === 'unread' && isUnread)
                            || (activeMsgTab === 'unanswered' && isUnanswered)
                            || (activeMsgTab === 'answered' && isAnswered);
                        var show = matchRider && matchOs && matchCustomer && matchTab;
                        $row.toggleClass('d-none', !show);
                        if (show) visible += 1;
                    });

                    $('#assignedItemCount').text(visible);
                    $('#assignedFilterEmptyState').toggleClass('d-none', visible !== 0);
                    $('#assignedTableShell').toggleClass('d-none', visible === 0);
                }

                var filterTimer;
                $('#assigned_rider_filter, #assigned_os_name, #assigned_customer_search').on('input', function () {
                    clearTimeout(filterTimer);
                    filterTimer = setTimeout(applyAssignedFilters, 150);
                });

                $('#assignedMsgTabs').on('click', '[data-msg-tab]', function () {
                    var tab = String($(this).data('msg-tab') || 'unread');
                    if (allowedMsgTabs.indexOf(tab) === -1) tab = 'unread';
                    activeMsgTab = tab;
                    try { sessionStorage.setItem('pdsMsgTab', tab); } catch (e) {}
                    $('#assignedMsgTabs .pds-msg-tabs__btn').removeClass('is-active');
                    $(this).addClass('is-active');
                    applyAssignedFilters();
                });

                $('#assignedMsgTabs .pds-msg-tabs__btn').removeClass('is-active');
                $('#assignedMsgTabs [data-msg-tab="' + activeMsgTab + '"]').addClass('is-active');
                applyAssignedFilters();

                $(document).on('click', '.pds-dispatch-action-edit.loadRemoteModel', function (e) {
                    e.stopPropagation();
                });
            });
        </script>
    @endsection
</x-master-layout>
