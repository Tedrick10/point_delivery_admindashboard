@php
    $activeCategory = $active_category ?? 'all';
    $categories = [
        'all' => __('message.notification_category_all'),
        'user_messages' => __('message.notification_category_user_messages'),
        'user_orders' => __('message.notification_category_user_orders'),
        'admin_changes' => __('message.notification_category_admin_changes'),
    ];
@endphp
<div class="p-3 card-header-border">
    <h6 class="text-center">
        {{ __('message.notification') }}   <small class="badge badge-light float-right pt-1 notification_count notification_tag"> {{ $all_unread_count }}</small>
    </h6>
</div>
<div class="px-2 py-2">
    <div class="pds-notification-tabs mb-2">
        @foreach($categories as $key => $label)
            <button type="button"
                    class="pds-notification-tab {{ $activeCategory === $key ? 'is-active' : '' }}"
                    data-category="{{ $key }}">
                {{ $label }}
            </button>
        @endforeach
    </div>
    <h6 class="text-sm text-muted m-0"><span class="notification_count">{{  __('message.you_have_unread_notification',['number' => $all_unread_count ]) }}</span>
        @if($all_unread_count > 0 )
            <a href="#" data-type="markas_read" class="notifyList float-right" ><span>{{ __('message.mark_all_as_read') }}</span></a>
        @endif
    </h6>
</div>

@if(isset($notifications) && count($notifications) > 0)
    <div class="notification-height">
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
            <a href="{{ $route }}" class="sub-card {{ $notification->read_at ? '':'notify-list-bg'}}">
            @else
            <div class="sub-card {{ $notification->read_at ? '':'notify-list-bg'}}">
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
    </div>
    <a href="{{ route('notification.index', ['category' => $activeCategory !== 'all' ? $activeCategory : null]) }}" class="dropdown-item text-center text-primary font-weight-bold py-3">
        {{ __('message.view_all') }}
    </a>
@else
    <a href="#" class="sub-card">
        <div class="media align-items-center">
            <div class="media-body ml-3">
                <h6 class="mb-0">{{ __('message.no_notification') }}</h6>
                <small class="float-right font-size-12"></small>
                <p class="mb-0"></p>
            </div>
        </div>
    </a>
@endif
