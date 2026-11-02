@php
    $screens = config('super_admin_screens', []);
    $periodQuery = request()->only(['period', 'date', 'date_from', 'date_to', 'month']);
    $periodQuery = array_filter($periodQuery, fn ($value) => $value !== null && $value !== '');
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
            'key' => 'overview',
            'label' => __('message.sa_nav_overview'),
            'items' => [
                [
                    'key' => 'dashboard',
                    'href' => $withPeriod(route('super-admin.dashboard')),
                    'icon' => 'fa-chart-pie',
                    'label' => __('message.dashboard'),
                    'active' => request()->routeIs('super-admin.dashboard'),
                ],
                [
                    'key' => 'all-screens',
                    'href' => $withPeriod(route('super-admin.screens.hub')),
                    'icon' => 'fa-th-large',
                    'label' => __('message.sa_all_screens'),
                    'active' => request()->routeIs('super-admin.screens.hub'),
                ],
                [
                    'key' => 'branches',
                    'href' => $withPeriod(route('super-admin.branches.index')),
                    'icon' => 'fa-code-branch',
                    'label' => __('message.sa_branches_title'),
                    'active' => request()->routeIs('super-admin.branches.*'),
                ],
                [
                    'key' => 'branch-admins',
                    'href' => $withPeriod(route('super-admin.branch-admins.index')),
                    'icon' => 'fa-user-shield',
                    'label' => __('message.sa_branch_admins'),
                    'active' => request()->routeIs('super-admin.branch-admins.*'),
                ],
                [
                    'key' => 'admin-panel-ui',
                    'href' => $withPeriod(route('home')),
                    'icon' => 'fa-desktop',
                    'label' => 'Admin Panel UI',
                    'active' => request()->routeIs('home'),
                ],
            ],
        ],
        [
            'key' => 'operations',
            'label' => __('message.sa_operations'),
            'keys' => ['delivery-route'],
        ],
        [
            'key' => 'finance',
            'label' => __('message.sa_nav_finance'),
            'keys' => ['kyo-shin', 'rider-remit', 'expense-summary', 'point-system'],
        ],
        [
            'key' => 'people',
            'label' => __('message.sa_nav_people'),
            'keys' => ['account-creation', 'roles-permissions', 'office-salary', 'rider-salary', 'late-fine', 'welcome-promotion'],
        ],
        [
            'key' => 'settings',
            'label' => __('message.sa_nav_settings'),
            'keys' => ['general-setting', 'company-contact', 'api-server-setting', 'app-store-update', 'app-copy'],
        ],
    ];
@endphp

@foreach($navGroups as $group)
    <div class="sa-nav__group">
        <div class="sa-nav__label"
             data-sa-edit-text="1"
             data-sa-text-key="nav.group.{{ $group['key'] }}">{{ saUiText('nav.group.'.$group['key'], $group['label']) }}</div>
        @if(!empty($group['items']))
            @foreach($group['items'] as $item)
                <a href="{{ $item['href'] }}" class="{{ !empty($item['active']) ? 'active' : '' }}">
                    <span class="sa-nav__ico"><i class="fas {{ $item['icon'] }} fa-fw" aria-hidden="true"></i></span>
                    <span class="sa-nav__text"
                          data-sa-edit-text="1"
                          data-sa-text-key="nav.item.{{ $item['key'] }}">{{ saUiText('nav.item.'.$item['key'], $item['label']) }}</span>
                </a>
            @endforeach
        @endif
        @foreach(($group['keys'] ?? []) as $key)
            @continue(empty($screens[$key]))
            @php $screen = $screens[$key]; @endphp
            <a href="{{ $screenUrl($key) }}" class="{{ $isScreen($key) ? 'active' : '' }}">
                <span class="sa-nav__ico"><i class="fas {{ $screen['icon'] ?? 'fa-circle' }} fa-fw" aria-hidden="true"></i></span>
                <span class="sa-nav__text"
                      data-sa-edit-text="1"
                      data-sa-text-key="nav.screen.{{ $key }}">{{ saUiText('nav.screen.'.$key, __('message.'.($screen['title_key'] ?? 'sa_screen_dispatch'))) }}</span>
            </a>
        @endforeach
    </div>
@endforeach
