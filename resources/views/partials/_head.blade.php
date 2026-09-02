<link rel="shortcut icon" class="site_favicon_preview" href="{{ getSingleMedia(appSettingData('get'), 'site_favicon', null) }}" />
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">
<link rel="stylesheet" href="{{ public_asset_ver('css/backend-bundle.min.css') }}"/>
<link rel="stylesheet" href="{{ public_asset_ver('css/backend.css') }}"/>
@if(mighty_language_direction() == 'rtl')
    <link rel="stylesheet" href="{{ public_asset_ver('css/rtl.css') }}">
@endif
<link rel="stylesheet" href="{{ public_asset_ver('vendor/@fortawesome/fontawesome-free/css/all.min.css') }}"/>
<link rel="stylesheet" href="{{ public_asset_ver('vendor/remixicon/fonts/remixicon.css') }}"/>
<link rel="stylesheet" href="{{ public_asset_ver('css/vendor/select2.min.css') }}">
<link rel="stylesheet" href="{{ public_asset_ver('vendor/confirmJS/jquery-confirm.min.css') }}"/>
<link rel="stylesheet" href="{{ public_asset_ver('vendor/magnific-popup/css/magnific-popup.css') }}"/>
{{-- Inline PDS theme CSS so php artisan serve cannot truncate linked stylesheets. --}}
@php
    $pdsInlineCss = trim(
        public_css_inline('css/custom.css')."\n".
        public_css_inline('css/admin-dashboard-theme.css')."\n".
        public_css_inline('css/pds-layout.css')
    );
@endphp
@if($pdsInlineCss !== '')
    <style id="pds-admin-theme-inline">{!! $pdsInlineCss !!}</style>
@else
    <link rel="stylesheet" href="{{ public_asset_ver('css/custom.css') }}">
    <link rel="stylesheet" href="{{ public_asset_ver('css/admin-dashboard-theme.css') }}">
    <link rel="stylesheet" href="{{ public_asset_ver('css/pds-layout.css') }}">
@endif
<style>
    /* Keep page content visible if enter-animations glitch. */
    body.pds-admin .pds-page-wrap,
    body.pds-admin .pds-motion-enter,
    body.pds-admin .content-page {
        opacity: 1 !important;
        visibility: visible !important;
    }
    body.pds-admin .pds-profile-menu__logout-btn {
        -webkit-appearance: none !important;
        appearance: none !important;
        border: 0 !important;
        border-radius: 0 !important;
        outline: 0 !important;
        box-shadow: none !important;
        background: transparent !important;
        width: 100% !important;
        display: flex !important;
        align-items: center !important;
        gap: 0.65rem !important;
        padding: 0.7rem 1rem !important;
        margin: 0 !important;
        font: inherit !important;
        font-weight: 600 !important;
        font-size: 0.86rem !important;
        color: #c2410c !important;
        cursor: pointer !important;
        text-align: left !important;
    }
</style>
@if(isset($assets) && in_array('phone', $assets))
    <link rel="stylesheet" href="{{ public_asset_ver('vendor/intlTelInput/css/intlTelInput.css') }}">
@endif
<link rel="stylesheet" href="{{ public_asset_ver('css/sweetalert2.min.css') }}">
