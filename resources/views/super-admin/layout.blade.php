@php
    $saCssPath = public_path('css/super-admin-panel.css');
    $saCssVersion = file_exists($saCssPath) ? (string) filemtime($saCssPath) : (string) time();
    $saCssInline = file_exists($saCssPath) ? file_get_contents($saCssPath) : '';
    $saCssUrl = url('/css/super-admin-panel.css').'?v='.$saCssVersion;
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
    <title>@yield('title', __('message.sa_super_admin')) — {{ SettingData('app_content', 'app_name') ?? config('app.name') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ getSingleMedia(appSettingData('get'), 'site_favicon', null) }}">
    <style id="sa-panel-styles">{!! $saCssInline !!}</style>
    <link rel="stylesheet" href="{{ url('/vendor/@fortawesome/fontawesome-free/css/all.min.css') }}">
    @stack('styles')
</head>
<body class="sa-panel" style="margin:0;min-height:100vh;display:grid;grid-template-columns:248px 1fr;">
    <aside class="sa-sidebar" style="background:#0c1222;color:#fff;min-height:100vh;">
        <div class="sa-brand">
            <span class="sa-brand-mark">SA</span>
            <div>
                <div class="sa-brand-name">{{ __('message.sa_super_admin') }}</div>
                <div class="sa-brand-sub">{{ SettingData('app_content', 'app_name') }}</div>
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
            <div>
                <h1 class="sa-page-title">@yield('page_title', __('message.dashboard'))</h1>
                @hasSection('page_sub')
                    <p class="sa-page-sub">@yield('page_sub')</p>
                @endif
            </div>
            <div class="sa-topbar-tools">
                @include('super-admin.partials.language-switcher')
                <div class="sa-topbar-meta">{{ now('Asia/Yangon')->locale(app()->getLocale())->translatedFormat('D, d M Y') }}</div>
            </div>
        </header>

        @include('super-admin.partials.date-filter')

        @if(session('success'))
            <div class="alert alert-success sa-alert">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger sa-alert">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger sa-alert">
                <ul class="mb-0 pl-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(!request()->routeIs('super-admin.dashboard'))
            @include('super-admin.partials.shared-stats')
        @endif

        @yield('content')
    </main>
    <script>
    (function () {
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });

        var css = document.getElementById('sa-panel-styles');
        if (css && !css.textContent.trim()) {
            var link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = @json($saCssUrl);
            document.head.appendChild(link);
        }
    })();
    </script>
    @stack('scripts')
</body>
</html>
