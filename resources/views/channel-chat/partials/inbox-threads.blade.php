@if($supports->isEmpty())
    <div class="pds-dispatch-to-assign-empty pds-msg-empty" id="channelEmptyState">
        <span class="pds-msg-empty__icon" aria-hidden="true"><i class="far fa-comments"></i></span>
        <p>{{ __('message.no_record_found') }}</p>
    </div>
@else
    <div class="pds-dispatch-to-assign-empty pds-msg-empty d-none" id="channelFilterEmptyState">
        <span class="pds-msg-empty__icon" aria-hidden="true"><i class="far fa-comments"></i></span>
        <p>{{ __('message.no_record_found') }}</p>
    </div>
    <div class="pds-msg-thread-list" id="channelTableShell">
        @foreach($supports as $row)
            @php
                $userName = trim((string) optional($row->user)->name) ?: 'User';
                $unread = ! empty($row->is_unread) ? 1 : 0;
                $needsReply = ! empty($row->needs_reply) ? 1 : 0;
                $time = $row->last_chat_at
                    ? \Carbon\Carbon::parse($row->last_chat_at)->timezone('Asia/Yangon')->format('d-m H:i')
                    : '';
                $avatar = getSingleMedia($row->user, 'profile_image', null);
                $initial = function_exists('resolveNameInitial') ? resolveNameInitial($userName) : strtoupper(substr($userName, 0, 1));
            @endphp
            <a
                href="{{ route('customersupport.show', $row->id) }}"
                class="pds-msg-thread-card {{ $unread ? 'is-unread' : '' }} {{ $needsReply ? 'is-unanswered' : 'is-answered' }}"
                style="text-decoration:none;color:inherit;"
                data-unread="{{ $unread }}"
                data-unanswered="{{ $needsReply }}"
                data-answered="{{ $needsReply ? 0 : 1 }}"
            >
                <div class="pds-msg-thread-card__avatar {{ $avatar ? 'has-photo' : '' }}" aria-hidden="true">
                    @if($avatar)
                        <img src="{{ $avatar }}" alt="{{ $userName }}">
                    @else
                        {{ $initial }}
                    @endif
                </div>
                <div class="pds-msg-thread-card__body">
                    <div class="pds-msg-thread-card__line1">
                        <strong class="pds-msg-thread-card__parcel">{{ $userName }}</strong>
                        @if($time !== '')
                            <span class="pds-msg-thread-card__time">{{ $time }}</span>
                        @endif
                    </div>
                    <div class="pds-msg-thread-card__line2">
                        #{{ $row->id }} · {{ $row->support_type }}
                    </div>
                    <div class="pds-msg-thread-card__line3">
                        <span class="pds-msg-thread-card__sender">({{ $row->last_chat_sender_label }})</span>
                        <span class="pds-msg-thread-card__preview">{{ $row->last_chat_message }}</span>
                    </div>
                </div>
                <div class="pds-msg-thread-card__aside">
                    @if($unread)
                        <span class="pds-msg-thread-card__badge">1</span>
                    @endif
                </div>
            </a>
        @endforeach
    </div>
@endif
