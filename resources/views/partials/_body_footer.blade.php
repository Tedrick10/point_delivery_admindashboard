@php
    $footerLogo = getSingleMedia(appSettingData('get'), 'site_logo', null);
    $footerName = config('app.name', 'Point Delivery');
@endphp

<footer class="mm-footer pds-footer">
    <div class="container-fluid">
        <div class="pds-footer__inner">
            <div class="pds-footer__brand">
                @if($footerLogo)
                    <img src="{{ $footerLogo }}" alt="{{ $footerName }}" class="pds-footer__logo">
                @endif
                <span class="pds-footer__name">{{ $footerName }}</span>
            </div>
            <p class="pds-footer__copy mb-0">
                {{ __('message.copyright') }} {{ date('Y') }}
                <span class="pds-footer__dot">·</span>
                {{ __('message.all_rights_reserved') }}
            </p>
        </div>
    </div>
</footer>
