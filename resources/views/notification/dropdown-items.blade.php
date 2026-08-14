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
        @endphp
        @if($route)
            <a href="{{ $route }}" class="sub-card {{ $notification->read_at ? '' : 'notify-list-bg' }}">
        @else
            <div class="sub-card {{ $notification->read_at ? '' : 'notify-list-bg' }}">
        @endif
            <div class="media align-items-center">
                <div class="media-body ml-3">
                    <span class="pds-notification-category-badge pds-notification-category-badge--{{ $category }}">
                        {{ notificationCategoryLabel($category) }}
                    </span>
                    <h6 class="mb-0 mt-1">{{ $title }}</h6>
                    <small class="float-right font-size-12">
                        {{ timeAgoFormate($notification->created_at) }}
                    </small>
                    <p class="mb-0">{{ $message }}</p>
                </div>
            </div>
        @if($route)
            </a>
        @else
            </div>
        @endif
    @endforeach
    <a href="{{ route('notification.index', ['category' => $activeCategory !== 'all' ? $activeCategory : null]) }}"
       class="dropdown-item text-center text-primary font-weight-bold py-3 pds-notify-view-all">
        {{ __('message.view_all') }}
    </a>
@else
    <div class="sub-card pds-notify-empty">
        <div class="media align-items-center">
            <div class="media-body ml-3">
                <h6 class="mb-0">{{ __('message.no_notification') }}</h6>
            </div>
        </div>
    </div>
@endif
