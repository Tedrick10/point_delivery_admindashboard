@php
    $screens = config('super_admin_screens', []);
    $periodQuery = request()->query();
    $withPeriod = function (string $url) use ($periodQuery): string {
        if ($periodQuery === []) {
            return $url;
        }

        return $url.(str_contains($url, '?') ? '&' : '?').http_build_query($periodQuery);
    };
    $screenUrl = function (string $key) use ($withPeriod): string {
        return $withPeriod(route('super-admin.screens.show', $key));
    };
    $isScreen = fn (string $key) => request()->routeIs('super-admin.screens.show')
        && (string) request()->route('screen') === $key;

    $navGroups = [
        [
            'label' => __('message.sa_nav_overview'),
            'items' => [
                [
                    'href' => $withPeriod(route('super-admin.dashboard')),
                    'icon' => 'fa-chart-pie',
                    'label' => __('message.dashboard'),
                    'active' => request()->routeIs('super-admin.dashboard'),
                ],
                [
                    'href' => $withPeriod(route('super-admin.screens.hub')),
                    'icon' => 'fa-th-large',
                    'label' => __('message.sa_all_screens'),
                    'active' => request()->routeIs('super-admin.screens.hub'),
                ],
                [
                    'href' => $withPeriod(route('super-admin.branch-admins.index')),
                    'icon' => 'fa-user-shield',
                    'label' => __('message.sa_branch_admins'),
                    'active' => request()->routeIs('super-admin.branch-admins.*'),
                ],
            ],
        ],
        [
            'label' => __('message.sa_operations'),
            'keys' => ['delivery-route'],
        ],
        [
            'label' => __('message.sa_nav_finance'),
            'keys' => ['kyo-shin', 'rider-remit', 'expense-summary'],
        ],
        [
            'label' => __('message.sa_nav_people'),
            'keys' => ['account-creation', 'roles-permissions', 'office-salary', 'rider-salary', 'late-fine', 'welcome-promotion'],
        ],
        [
            'label' => __('message.sa_nav_settings'),
            'keys' => ['general-setting', 'company-contact', 'api-server-setting', 'app-store-update', 'ui-theme', 'app-copy'],
        ],
    ];
@endphp

@foreach($navGroups as $group)
    <div class="sa-nav__group">
        <div class="sa-nav__label">{{ $group['label'] }}</div>
        @if(!empty($group['items']))
            @foreach($group['items'] as $item)
                <a href="{{ $item['href'] }}" class="{{ !empty($item['active']) ? 'active' : '' }}">
                    <span class="sa-nav__ico"><i class="fas {{ $item['icon'] }} fa-fw" aria-hidden="true"></i></span>
                    <span class="sa-nav__text">{{ $item['label'] }}</span>
                </a>
            @endforeach
        @endif
        @foreach(($group['keys'] ?? []) as $key)
            @continue(empty($screens[$key]))
            @php $screen = $screens[$key]; @endphp
            <a href="{{ $screenUrl($key) }}" class="{{ $isScreen($key) ? 'active' : '' }}">
                <span class="sa-nav__ico"><i class="fas {{ $screen['icon'] ?? 'fa-circle' }} fa-fw" aria-hidden="true"></i></span>
                <span class="sa-nav__text">{{ __('message.'.($screen['title_key'] ?? 'sa_screen_dispatch')) }}</span>
            </a>
        @endforeach
    </div>
@endforeach
