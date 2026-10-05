@php
    $saCssPath = public_path('css/super-admin-panel.css');
    $saCssVersion = file_exists($saCssPath) ? (string) filemtime($saCssPath) : (string) time();
    $saCssUrl = url('/css/super-admin-panel.css').'?v='.$saCssVersion;
    $saScreenKey = (string) request()->route('screen');
    $saBodyClass = 'sa-panel'
        .(request()->routeIs('super-admin.screens.show') ? ' sa-page-module' : '')
        .(request()->routeIs('super-admin.screens.hub') ? ' sa-page-hub' : '')
        .($saScreenKey !== '' ? ' sa-screen-'.$saScreenKey : '');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('message.sa_super_admin')) — {{ appCopy('admin', 'title') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ getSingleMedia(appSettingData('get'), 'site_favicon', null) }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700;9..144,800&family=Outfit:wght@400;500;600;700;800&family=Noto+Sans+Myanmar:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ $saCssUrl }}">
    <link rel="stylesheet" href="{{ url('/vendor/@fortawesome/fontawesome-free/css/all.min.css') }}">
    @stack('styles')
</head>
<body class="{{ $saBodyClass }}">
    <aside class="sa-sidebar">
        <div class="sa-brand">
            @php $saLogo = getSingleMedia(appSettingData('get'), 'site_logo', null); @endphp
            @if($saLogo)
                <img class="sa-brand-logo" src="{{ $saLogo }}" alt="">
            @else
                <span class="sa-brand-mark">SA</span>
            @endif
            <div class="sa-brand-copy">
                <div class="sa-brand-name">{{ appCopy('admin', 'title') }}</div>
                <div class="sa-brand-sub">{{ __('message.sa_super_admin') }}</div>
            </div>
        </div>
        <nav class="sa-nav">
            @include('super-admin.partials.sidebar-nav')
        </nav>
        <div class="sa-sidebar-foot">
            <div class="sa-user">{{ Auth::user()->name }}</div>
            <form method="POST" action="{{ route('super-admin.logout') }}">
                @csrf
                <button type="submit" class="sa-logout"><i class="fas fa-sign-out-alt"></i> {{ __('message.logout') }}</button>
            </form>
        </div>
    </aside>
    <main class="sa-main">
        <header class="sa-topbar">
            <div class="sa-topbar-tools">
                @include('super-admin.partials.language-switcher')
                <div class="sa-topbar-meta">{{ now('Asia/Yangon')->locale(app()->getLocale())->translatedFormat('D, d M Y') }}</div>
            </div>
        </header>

        <div class="sa-content">
        <header class="sa-pagehead">
            <p>{{ __('message.sa_super_admin') }}</p>
            <h1>@yield('page_title', __('message.dashboard'))</h1>
            @hasSection('page_sub')
                <span>@yield('page_sub')</span>
            @endif
        </header>

        @if(request()->routeIs('super-admin.dashboard'))
            @include('super-admin.partials.date-filter')
        @endif

        @if(session('success'))
            <div class="sa-toast sa-toast--ok" role="status">
                <span class="sa-toast__icon" aria-hidden="true"><i class="fas fa-check"></i></span>
                <span class="sa-toast__text">{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="sa-toast sa-toast--err" role="alert">
                <span class="sa-toast__icon" aria-hidden="true"><i class="fas fa-exclamation"></i></span>
                <span class="sa-toast__text">{{ session('error') }}</span>
            </div>
        @endif
        @if ($errors->any())
            <div class="sa-toast sa-toast--err" role="alert">
                <span class="sa-toast__icon" aria-hidden="true"><i class="fas fa-exclamation"></i></span>
                <div class="sa-toast__text">
                    <ul class="mb-0 pl-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @yield('content')
        </div>
    </main>
    <script>
    (function () {
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });

        var active = document.querySelector('.sa-nav a.active');
        if (active && active.scrollIntoView) {
            active.scrollIntoView({ block: 'nearest' });
        }
    })();
    </script>
    <script src="{{ public_asset_ver('js/rabbit.js') }}"></script>
    <script src="{{ public_asset_ver('js/myanmar-text.js') }}"></script>
    @stack('scripts')
</body>
</html>
