@php
    $activeCategory = $active_category ?? 'all';
@endphp

@if(isset($notifications) && count($notifications) > 0)
    @foreach($notifications->sortByDesc('created_at')->take(5) as $notification)
        @php
            $data = is_array($notification->data) ? $notification->data : [];
            $category = inferNotificationCategory($data);
            $route = notificationRouteForData($data);
            $notification_id = $data['support_id'] ?? ($data['id'] ?? null);
            $title = $data['subject'] ?? ('#' . $notification_id . ' ' . str_replace('_', ' ', ucfirst(strtolower($data['type'] ?? ''))));
            $message = $data['message'] ?? __('message.booked');
            if (is_array($message)) {
                $message = implode(', ', $message);
            }
            $isUnread = empty($notification->read_at);
            $cardClass = 'pds-notify-card' . ($isUnread ? ' is-unread' : '');
        @endphp
        @if($route)
            <a href="{{ $route }}" class="{{ $cardClass }}">
        @else
            <div class="{{ $cardClass }}">
        @endif
            <div class="pds-notify-card__top">
                <span class="pds-notification-category-badge pds-notification-category-badge--{{ $category }}">
                    {{ notificationCategoryLabel($category) }}
                </span>
                <time class="pds-notify-card__time">{{ timeAgoFormate($notification->created_at) }}</time>
            </div>
            <h6 class="pds-notify-card__title">{{ $title }}</h6>
            <p class="pds-notify-card__message">{{ $message }}</p>
            @if($isUnread)
                <span class="pds-notify-card__dot" aria-hidden="true"></span>
            @endif
        @if($route)
            </a>
        @else
            </div>
        @endif
    @endforeach
    <a href="{{ route('notification.index', ['category' => $activeCategory !== 'all' ? $activeCategory : null]) }}"
       class="pds-notify-view-all">
        {{ __('message.view_all') }}
    </a>
@else
    <div class="pds-notify-empty">
        <span class="pds-notify-empty__icon" aria-hidden="true"><i class="far fa-bell-slash"></i></span>
        <p>{{ __('message.no_notification') }}</p>
    </div>
@endif
