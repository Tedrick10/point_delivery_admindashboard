<div class="mm-top-navbar pds-topbar">
    <div class="mm-navbar-custom">
        <nav class="navbar navbar-expand-lg navbar-light p-0 pds-topbar-nav">
            <div class="mm-navbar-logo d-flex align-items-center justify-content-between pds-topbar-brand">
                <button type="button" class="pds-topbar-menu-btn wrapper-menu" aria-label="Toggle menu">
                    <i class="fas fa-bars"></i>
                </button>
                <a href="{{ route('home') }}" class="header-logo pds-topbar-logo-link d-none d-md-inline-flex">
                    <img src="{{ getSingleMedia(appSettingData('get'),'site_logo',null) }}" class="img-fluid mode light-img rounded-normal site_logo_preview pds-topbar-logo" alt="logo">
                    <img src="{{ getSingleMedia(appSettingData('get'),'site_dark_logo',null) }}" class="img-fluid mode dark-img rounded-normal darkmode-logo site_dark_logo_preview pds-topbar-logo" alt="dark-logo">
                </a>
            </div>
            <div class="mm-search-bar device-search m-auto"></div>
            <div class="d-flex align-items-center pds-topbar-actions">
                @if(SettingData('emergency','alert_icon') == 1)
                    @php $emergencyCount = \App\Models\Emergency::where('status', 0)->count() ?? 0; @endphp
                    @if ($emergencyCount > 0)
                        <a href="{{ route('emergency.index') }}" class="pds-topbar-emergency" title="{{ __('message.emergency') }}">
                            <span class="heartbeat-dot"></span>
                        </a>
                    @endif
                @endif

                <div class="pds-topbar-cluster">
                    <div class="pds-topbar-group pds-topbar-group--theme">
                        <div class="change-mode pds-topbar-theme" title="{{ __('message.dark_mode') ?? 'Theme' }}">
                            <div class="custom-control custom-switch custom-switch-icon custom-control-inline mb-0">
                                <div class="custom-switch-inner">
                                    <input type="checkbox" class="custom-control-input" id="dark-mode" data-active="true" aria-label="Toggle dark mode">
                                    <label class="custom-control-label" for="dark-mode" data-mode="toggle">
                                        <span class="switch-icon-left">
                                            <svg class="svg-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                            </svg>
                                        </span>
                                        <span class="switch-icon-right">
                                            <svg class="svg-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                                            </svg>
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <span class="pds-topbar-divider" aria-hidden="true"></span>

                    <button class="navbar-toggler pds-topbar-mobile-toggle" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-label="Toggle navigation">
                        <i class="ri-menu-3-line"></i>
                    </button>

                    <div class="collapse navbar-collapse pds-topbar-group pds-topbar-group--tools" id="navbarSupportedContent">
                        <ul class="navbar-nav ml-auto navbar-list align-items-center pds-topbar-tools">
                            <li class="nav-item nav-icon dropdown pds-notify-dropdown">
                                <a href="#" class="pds-topbar-tool pds-topbar-notify notification_list" id="pdsNotificationDropdownToggle" aria-haspopup="true" aria-expanded="false" title="{{ __('message.notification') }}">
                                    <span class="pds-topbar-tool-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                                    </span>
                                    <span class="pds-topbar-badge notify_count count-mail d-none"></span>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right mm-sub-dropdown notification-menu pds-notify-menu" aria-labelledby="pdsNotificationDropdownToggle">
                                    <div class="pds-notify-menu__header">
                                        <span>{{ __('message.notification') }}</span>
                                        <div class="pds-notify-menu__header-actions">
                                            <small class="badge badge-light pds-notify-header-count notification_count notification_tag">0</small>
                                            <button type="button" class="pds-notify-close" onclick="return window.pdsCloseNotificationDropdown(event);" aria-label="{{ __('message.close') }}">
                                                <i class="ri-close-line" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="pds-notify-menu__tabs pds-notification-tabs" id="pdsNotificationTabs" role="tablist">
                                        <a href="javascript:void(0)" role="tab" class="pds-notification-tab is-active" data-category="all" onclick="return window.pdsFilterNotifications('all', this, event);">{{ __('message.notification_category_all') }}</a>
                                        <a href="javascript:void(0)" role="tab" class="pds-notification-tab" data-category="user_messages" onclick="return window.pdsFilterNotifications('user_messages', this, event);">{{ __('message.notification_category_user_messages') }}</a>
                                        <a href="javascript:void(0)" role="tab" class="pds-notification-tab" data-category="user_orders" onclick="return window.pdsFilterNotifications('user_orders', this, event);">{{ __('message.notification_category_user_orders') }}</a>
                                        <a href="javascript:void(0)" role="tab" class="pds-notification-tab" data-category="admin_changes" onclick="return window.pdsFilterNotifications('admin_changes', this, event);">{{ __('message.notification_category_admin_changes') }}</a>
                                    </div>
                                    <div class="pds-notify-menu__meta">
                                        <span class="pds-notify-unread-text notification_count">{{ __('message.you_have_unread_notification', ['number' => 0]) }}</span>
                                        <a href="#" data-type="markas_read" class="notifyList pds-notify-mark-read d-none">{{ __('message.mark_all_as_read') }}</a>
                                    </div>
                                    <div class="pds-notify-menu__body notification_data"></div>
                                </div>
                            </li>

                            <li class="nav-item nav-icon dropdown">
                                <a href="#" class="pds-topbar-tool pds-topbar-lang-btn" id="languageDropdownMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="{{ __('message.language') }}">
                                    <span class="pds-topbar-lang-inner">
                                        <span class="pds-topbar-flag-wrap">
                                            <img src="{{ languageFlagUrl(app()->getLocale()) }}" class="pds-topbar-flag" alt="{{ app()->getLocale() }}">
                                        </span>
                                        <span class="pds-topbar-lang-code d-none d-lg-inline">{{ strtoupper(app()->getLocale()) }}</span>
                                        <i class="fas fa-chevron-down pds-topbar-lang-chevron d-none d-lg-inline" aria-hidden="true"></i>
                                    </span>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right mm-sub-dropdown language-menu pds-lang-menu" aria-labelledby="languageDropdownMenu">
                                <div class="pds-lang-menu__header">{{ __('message.select_language') }}</div>
                                <ul class="pds-lang-menu__list list-unstyled mb-0">
                                    @php
                                        $language_array = [];
                                        $language_option = appSettingData('get')?->language_option;
                                        if (!empty($language_option)) {
                                            $language_array = languagesArray($language_option);
                                        }
                                        $currentLocale = app()->getLocale();
                                    @endphp
                                    @forelse($language_array as $lang)
                                        @php
                                            $isActive = $currentLocale === $lang['id'];
                                            $label = $lang['native'] ?? $lang['title'];
                                        @endphp
                                        <li class="pds-lang-menu__row">
                                            <a href="{{ route('change.language', ['locale' => $lang['id']]) }}"
                                               class="pds-lang-menu__item {{ $isActive ? 'is-active' : '' }}"
                                               data-lang="{{ $lang['id'] }}">
                                                <span class="pds-lang-menu__flag" aria-hidden="true">
                                                    <img src="{{ languageFlagUrl($lang['id']) }}" alt="">
                                                </span>
                                                <span class="pds-lang-menu__label">
                                                    <span class="pds-lang-menu__name" lang="{{ $lang['id'] }}">{{ $label }}</span>
                                                    @if(($lang['native'] ?? null) && ($lang['native'] !== $lang['title']))
                                                        <span class="pds-lang-menu__meta">{{ $lang['title'] }}</span>
                                                    @endif
                                                </span>
                                                <span class="pds-lang-menu__status" aria-hidden="true">
                                                    <i class="fas fa-check {{ $isActive ? '' : 'pds-lang-menu__check--hidden' }}"></i>
                                                </span>
                                            </a>
                                        </li>
                                    @empty
                                        <li class="pds-lang-menu__empty">{{ __('message.no_record_found') }}</li>
                                    @endforelse
                                </ul>
                            </div>
                        </li>

                        <li class="nav-item nav-icon dropdown full-screen d-none d-md-block">
                            <a href="#" class="pds-topbar-tool" id="btnFullscreen" title="{{ __('message.fullscreen') }}">
                                <span class="pds-topbar-tool-icon">
                                    <i class="max"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"></path></svg></i>
                                    <i class="min d-none"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3v3a2 2 0 0 1-2 2H3m18 0h-3a2 2 0 0 1-2-2V3m0 18v-3a2 2 0 0 1 2-2h3M3 16h3a2 2 0 0 1 2 2v3"></path></svg></i>
                                </span>
                            </a>
                        </li>
                        <li class="nav-item nav-icon dropdown d-md-none">
                            <a href="#" class="pds-topbar-profile pds-topbar-profile--mobile search-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="{{ __('message.my_profile') }}">
                                <span class="pds-topbar-profile-ring">
                                    <img src="{{ getSingleMedia(auth()->user(), 'profile_image', null) }}" class="pds-topbar-avatar avatar-rounded" alt="{{ auth()->user()->name }}">
                                </span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-right pds-topbar-profile-menu">
                                <li class="pds-profile-menu__head">
                                    <span class="pds-profile-menu__avatar-wrap">
                                        <img src="{{ getSingleMedia(auth()->user(), 'profile_image', null) }}" class="pds-profile-menu__avatar" alt="{{ auth()->user()->name }}">
                                    </span>
                                    <span class="pds-profile-menu__info">
                                        <strong class="pds-profile-menu__name">{{ auth()->user()->name }}</strong>
                                        <span class="pds-profile-menu__email">{{ auth()->user()->email }}</span>
                                    </span>
                                </li>
                                <li class="pds-profile-menu__divider" role="separator"></li>
                                <li class="dropdown-item d-flex">
                                    <svg class="svg-icon mr-0 text-primary" width="20" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <a href="{{ route('setting.index',['page' => 'profile_form']) }}">{{ __('message.my_profile') }}</a>
                                </li>
                                <li class="dropdown-item d-flex">
                                    <svg class="svg-icon mr-0 text-primary" width="20" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <a href="{{ route('setting.index') }}">{{ __('message.setting') }}</a>
                                </li>
                                <li class="dropdown-item d-flex">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <svg class="svg-icon mr-0 text-primary" width="20" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                        <a href="javascript:void(0)" class="pl-1" onclick="event.preventDefault(); this.closest('form').submit();">
                                            {{ __('message.logout') }}
                                        </a>
                                    </form>
                                </li>
                            </ul>
                        </li>
                        </ul>
                    </div>

                    <span class="pds-topbar-divider pds-topbar-divider--profile d-none d-md-inline-flex" aria-hidden="true"></span>

                    <div class="pds-topbar-group pds-topbar-group--profile d-none d-md-flex">
                        <div class="nav-item nav-icon dropdown">
                            <a href="#" class="pds-topbar-profile search-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="{{ __('message.my_profile') }}">
                                <span class="pds-topbar-profile-ring">
                                    <img src="{{ getSingleMedia(auth()->user(), 'profile_image', null) }}" class="pds-topbar-avatar avatar-rounded" alt="{{ auth()->user()->name }}">
                                </span>
                                <span class="pds-topbar-profile-meta d-none d-xl-flex">
                                    <span class="pds-topbar-profile-name">{{ auth()->user()->name }}</span>
                                    <i class="fas fa-chevron-down pds-topbar-profile-chevron" aria-hidden="true"></i>
                                </span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-right pds-topbar-profile-menu" aria-labelledby="dropdownMenuButton">
                                <li class="pds-profile-menu__head">
                                    <span class="pds-profile-menu__avatar-wrap">
                                        <img src="{{ getSingleMedia(auth()->user(), 'profile_image', null) }}" class="pds-profile-menu__avatar" alt="{{ auth()->user()->name }}">
                                    </span>
                                    <span class="pds-profile-menu__info">
                                        <strong class="pds-profile-menu__name">{{ auth()->user()->name }}</strong>
                                        <span class="pds-profile-menu__email">{{ auth()->user()->email }}</span>
                                    </span>
                                </li>
                                <li class="pds-profile-menu__divider" role="separator"></li>
                                <li class="dropdown-item d-flex">
                                    <svg class="svg-icon mr-0 text-primary" width="20" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <a href="{{ route('setting.index',['page' => 'profile_form']) }}">{{ __('message.my_profile') }}</a>
                                </li>
                                <li class="dropdown-item d-flex">
                                    <svg class="svg-icon mr-0 text-primary" width="20" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <a href="{{ route('setting.index') }}">{{ __('message.setting') }}</a>
                                </li>
                                <li class="dropdown-item d-flex">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <svg class="svg-icon mr-0 text-primary" width="20" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                        <a href="javascript:void(0)" class="pl-1" onclick="event.preventDefault(); this.closest('form').submit();">
                                            {{ __('message.logout') }}
                                        </a>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </div>
</div>

