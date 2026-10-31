<div class="container-fluid pds-page-wrap pds-motion-enter pds-dispatch-to-assign-page pds-dispatch-assigned-items-page"
     id="adminLiveRoot"
     data-live-soft="1"
     data-live-page="{{ ($channel ?? 'Inquiry') === 'Complaint' ? 'complaint.inbox' : 'inquiry.inbox' }}"
     data-inbox-version="{{ $inboxVersion ?? '' }}">
    <div class="pds-dispatch-to-assign-screen">
        <div class="pds-dispatch-to-assign-topbar">
            <div class="pds-dispatch-to-assign-topbar-copy">
                <h4 class="pds-dispatch-to-assign-heading">{{ $pageTitle }}</h4>
            </div>
            @if(($channel ?? '') === 'Inquiry')
                <a href="{{ route('inquiry-auto-replies.index') }}" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-robot mr-1"></i> {{ __('message.inquiry_auto_replies') }}
                </a>
            @endif
        </div>

        <div class="pds-dispatch-to-assign-filter pds-msg-toolbar">
            @php $tabCounts = $tabCounts ?? ['unread' => 0, 'unanswered' => 0, 'answered' => 0]; @endphp
            <div class="pds-msg-tabs" id="channelMsgTabs" role="tablist">
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

        <div class="pds-dispatch-to-assign-body" id="channelInboxBody">
            @include('channel-chat.partials.inbox-threads', ['supports' => $supports, 'channel' => $channel ?? 'Inquiry'])
        </div>
    </div>
</div>
