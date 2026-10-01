@php
    $splashLogo = getSingleMedia(appSettingData('get'), 'site_logo', null) ?: asset('images/default.png');
    $splashName = config('app.name', 'Point Delivery');
@endphp

<div id="loader" class="pds-splash" role="status" aria-live="polite" aria-label="{{ __('message.splash_loading') }}">
    <div class="pds-splash__canvas" aria-hidden="true">
        <span class="pds-splash__beam"></span>
        <span class="pds-splash__orb pds-splash__orb--1"></span>
        <span class="pds-splash__orb pds-splash__orb--2"></span>
        <span class="pds-splash__grid"></span>
    </div>

    <div class="pds-splash__hero">
        <div class="pds-splash__mark">
            <span class="pds-splash__ring pds-splash__ring--a" aria-hidden="true"></span>
            <span class="pds-splash__ring pds-splash__ring--b" aria-hidden="true"></span>
            <div class="pds-splash__logo-wrap">
                <img src="{{ $splashLogo }}" alt="{{ $splashName }}" class="pds-splash__logo" width="112" height="112">
            </div>
        </div>

        <div class="pds-splash__brand">
            <p class="pds-splash__title">{{ $splashName }}</p>
            <p class="pds-splash__subtitle">{{ __('message.splash_loading') }}</p>
        </div>

        <div class="pds-splash__progress" aria-hidden="true">
            <span class="pds-splash__progress-bar"></span>
        </div>
    </div>
</div>
