@php
    $screens = config('super_admin_screens', []);
    $navIcons = [
        'dispatch' => 'fa-truck-moving',
        'daily-check' => 'fa-clipboard-check',
        'money-transfer' => 'fa-exchange-alt',
        'rider-remit' => 'fa-motorcycle',
        'cash-payout' => 'fa-money-bill-wave',
        'os-receive' => 'fa-hand-holding-usd',
        'expenses' => 'fa-receipt',
        'expense-summary' => 'fa-file-invoice-dollar',
    ];
@endphp
<a href="{{ route('super-admin.dashboard') }}" class="{{ request()->routeIs('super-admin.dashboard') ? 'active' : '' }}">
    <i class="fas fa-chart-pie fa-fw" aria-hidden="true"></i> {{ __('message.dashboard') }}
</a>
<a href="{{ route('super-admin.screens.hub') }}" class="{{ request()->routeIs('super-admin.screens.*') ? 'active' : '' }}">
    <i class="fas fa-th-large fa-fw" aria-hidden="true"></i> {{ __('message.sa_all_screens') }}
</a>
<a href="{{ route('super-admin.branch-admins.index') }}" class="{{ request()->routeIs('super-admin.branch-admins.*') ? 'active' : '' }}">
    <i class="fas fa-user-shield fa-fw" aria-hidden="true"></i> {{ __('message.sa_branch_admins') }}
</a>

<div class="sa-nav__label">{{ __('message.sa_operations') }}</div>
@foreach($screens as $key => $screen)
    @php
        $icon = $navIcons[$key] ?? ($screen['icon'] ?? 'fa-circle');
        $screenUrl = route('super-admin.screens.show', $key);
        if (request()->query()) {
            $screenUrl .= '?'.http_build_query(request()->query());
        }
        $title = __('message.'.($screen['title_key'] ?? 'sa_screen_dispatch'));
    @endphp
    <a href="{{ $screenUrl }}"
       class="sa-nav__sub {{ request()->routeIs('super-admin.screens.show') && request()->route('screen') === $key ? 'active' : '' }}">
        <i class="fas {{ $icon }} fa-fw" aria-hidden="true"></i> {{ $title }}
    </a>
@endforeach
