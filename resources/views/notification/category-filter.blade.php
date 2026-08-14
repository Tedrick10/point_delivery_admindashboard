@php
    $activeCategory = $activeCategory ?? 'all';
    $categories = [
        'all' => __('message.notification_category_all'),
        'user_messages' => __('message.notification_category_user_messages'),
        'user_orders' => __('message.notification_category_user_orders'),
        'admin_changes' => __('message.notification_category_admin_changes'),
    ];
@endphp
<div class="pds-notification-tabs pds-notification-tabs--page">
    @foreach($categories as $key => $label)
        <a href="{{ route('notification.index', $key === 'all' ? [] : ['category' => $key]) }}"
           class="pds-notification-tab {{ $activeCategory === $key ? 'is-active' : '' }}">
            {{ $label }}
        </a>
    @endforeach
</div>
