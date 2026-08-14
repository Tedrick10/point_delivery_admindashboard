@php
    $splashLogo = getSingleMedia(appSettingData('get'), 'site_logo', null) ?: asset('images/default.png');
    $splashName = config('app.name', 'Point Delivery');
@endphp

<div id="loader" class="pds-splash" role="status" aria-live="polite" aria-label="Loading">
    <div class="pds-splash__glow pds-splash__glow--one"></div>
    <div class="pds-splash__glow pds-splash__glow--two"></div>

    <div class="pds-splash__content">
        <div class="pds-splash__logo-shell">
            <span class="pds-splash__ring pds-splash__ring--outer" aria-hidden="true"></span>
            <span class="pds-splash__ring pds-splash__ring--inner" aria-hidden="true"></span>
            <div class="pds-splash__logo-card">
                <img src="{{ $splashLogo }}" alt="{{ $splashName }}" class="pds-splash__logo">
            </div>
        </div>

        <h1 class="pds-splash__title">{{ $splashName }}</h1>
        <p class="pds-splash__subtitle">{{ __('message.splash_loading') }}</p>

        <div class="pds-splash__progress" aria-hidden="true">
            <span class="pds-splash__progress-bar"></span>
        </div>
    </div>
</div>
