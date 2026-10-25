<x-master-layout :assets="$assets ?? []">
    
<div id="adminLiveRoot" data-live-page="auto">
<div class="container-fluid pds-page-wrap pds-motion-enter pds-dispatch-to-assign-page pds-dispatch-assigned-items-page">
        <div class="pds-dispatch-to-assign-screen">
            <div class="pds-dispatch-to-assign-topbar">
                <div class="pds-dispatch-to-assign-topbar-copy">
                    <h4 class="pds-dispatch-to-assign-heading">{{ $pageTitle ?? __('message.assigned_item_list') }}</h4>
                </div>
            </div>

            <div class="pds-dispatch-to-assign-filter pds-msg-toolbar">
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
            </div>

            <div class="pds-dispatch-to-assign-body">
                @if($items->isEmpty())
                    <div class="pds-dispatch-to-assign-empty pds-msg-empty" id="assignedEmptyState">
                        <span class="pds-msg-empty__icon" aria-hidden="true"><i class="far fa-comments"></i></span>
                        <p>{{ __('message.no_record_found') }}</p>
                    </div>
                @else
                    <div class="pds-dispatch-to-assign-empty pds-msg-empty d-none" id="assignedFilterEmptyState">
                        <span class="pds-msg-empty__icon" aria-hidden="true"><i class="far fa-comments"></i></span>
                        <p>{{ __('message.no_record_found') }}</p>
                    </div>
                    <div class="pds-msg-thread-list" id="assignedTableShell">
                        @foreach($items as $item)
                            @php
                                $order = $item->order;
                                $osName = resolveDispatchOsName($order);
                                $osUsername = trim((string) optional($order)->client?->username);
                                $osPhone = resolveDispatchOsPhone($order);
                                $osProfileImage = resolveUploadedProfileImageUrl(optional($order)->client);
                                $osInitial = resolveNameInitial($osName !== '-' ? $osName : '');
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
                            @endphp
                            <div
                                class="pds-msg-thread-card {{ $unread > 0 ? 'is-unread' : '' }} {{ $needsReply ? 'is-unanswered' : 'is-answered' }}"
                                data-os-name="{{ $osName !== '-' ? $osName : '' }}"
                                data-os-username="{{ $osUsername }}"
                                data-os-phone="{{ $osPhone !== '-' ? $osPhone : '' }}"
                                data-rider-name="{{ $deliveryRider !== '-' ? $deliveryRider : '' }}"
                                data-customer-name="{{ $customerName }}"
                                data-customer-phone="{{ $customerPhone }}"
                                data-item-id="{{ $item->id }}"
                                data-unread="{{ $unread > 0 ? 1 : 0 }}"
                                data-unanswered="{{ $needsReply }}"
                                data-answered="{{ $needsReply ? 0 : 1 }}"
                            >
                                <div class="pds-msg-thread-card__avatar {{ $osProfileImage ? 'has-photo' : '' }}" title="{{ $osName !== '-' ? $osName : 'OS' }}" aria-hidden="true">
                                    @if($osProfileImage)
                                        <img src="{{ $osProfileImage }}" alt="{{ $osName !== '-' ? $osName : 'OS' }}">
                                    @else
                                        {{ $osInitial }}
                                    @endif
                                </div>
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
                                        <span class="pds-msg-thread-card__preview">{{ $preview }}</span>
                                    </div>
                                </div>
                                <div class="pds-msg-thread-card__aside">
                                    @if($unread > 0)
                                        <span class="pds-msg-thread-card__badge">{{ $unread > 99 ? '99+' : $unread }}</span>
                                    @endif
                                    @include('order.dispatch-item-action', ['item' => $item, 'hideGate' => true, 'hideEdit' => true])
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    @include('order.partials._dispatch-item-message-modal')
</div>


    @section('bottom_script')
        @include('order.partials._dispatch-item-message-scripts')
        <script>
            $(document).ready(function () {
                window.reloadDispatchItemsTable = function () {
                    typeof window.adminLiveReloadPage === 'function' ? window.adminLiveReloadPage() : window.location.reload();
                };

                var allowedMsgTabs = ['unread', 'unanswered', 'answered'];
                var savedMsgTab = '';
                try { savedMsgTab = String(sessionStorage.getItem('pdsMsgTab') || ''); } catch (e) {}
                var activeMsgTab = allowedMsgTabs.indexOf(savedMsgTab) !== -1 ? savedMsgTab : 'unread';

                function applyAssignedFilters() {
                    var visible = 0;

                    $('#assignedTableShell .pds-msg-thread-card').each(function () {
                        var $row = $(this);
                        var isUnread = String($row.data('unread') || '0') === '1';
                        var isUnanswered = String($row.data('unanswered') || '0') === '1';
                        var isAnswered = String($row.data('answered') || '0') === '1';
                        var matchTab = (activeMsgTab === 'unread' && isUnread)
                            || (activeMsgTab === 'unanswered' && isUnanswered)
                            || (activeMsgTab === 'answered' && isAnswered);
                        $row.toggleClass('d-none', !matchTab);
                        if (matchTab) visible += 1;
                    });

                    $('#assignedFilterEmptyState').toggleClass('d-none', visible !== 0);
                    $('#assignedTableShell').toggleClass('d-none', visible === 0);
                }

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

                // Deep-link from Notification → open the matching chat thread.
                try {
                    var params = new URLSearchParams(window.location.search || '');
                    var openItemId = String(params.get('item_id') || '').trim();
                    if (openItemId) {
                        var $card = $('#assignedTableShell .pds-msg-thread-card[data-item-id="' + openItemId + '"]');
                        if ($card.length) {
                            $card.removeClass('d-none');
                            var $btn = $card.find('.js-dispatch-item-message').first();
                            if ($btn.length) {
                                setTimeout(function () { $btn.trigger('click'); }, 250);
                            }
                        }
                    }
                } catch (e) {}

                $(document).on('click', '.pds-dispatch-action-edit.loadRemoteModel', function (e) {
                    e.stopPropagation();
                });
            });
        </script>
    @endsection
</x-master-layout>
