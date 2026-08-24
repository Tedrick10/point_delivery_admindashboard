<link rel="shortcut icon" class="site_favicon_preview" href="{{ getSingleMedia(appSettingData('get'), 'site_favicon', null) }}" />
<link rel="stylesheet" href="{{ asset('css/backend-bundle.min.css') }}"/>
<link rel="stylesheet" href="{{ asset('css/backend.css') }}"/>
@if(mighty_language_direction() == 'rtl')
    <link rel="stylesheet" href="{{ asset('css/rtl.css') }}">
@endif
<link rel="stylesheet" href="{{ asset('vendor/@fortawesome/fontawesome-free/css/all.min.css') }}"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
<link rel="stylesheet" href="{{ asset('vendor/remixicon/fonts/remixicon.css') }}"/>
<link rel="stylesheet" href="{{ asset('css/vendor/select2.min.css')}}">
<link rel="stylesheet" href="{{ asset('vendor/confirmJS/jquery-confirm.min.css') }}"/>
<link rel="stylesheet" href="{{ asset('vendor/magnific-popup/css/magnific-popup.css') }}"/>
<link rel="stylesheet" href="{{ asset('css/custom.css')}}">
<link rel="stylesheet" href="{{ asset('css/admin-dashboard-theme.css') }}?v=208">
<link rel="stylesheet" href="{{ asset('css/pds-layout.css') }}?v=13">
<style>
    /* Logout button: kill browser default chrome even if theme CSS is cached */
    body.pds-admin .pds-profile-menu__logout-btn {
        -webkit-appearance: none !important;
        appearance: none !important;
        border: 0 !important;
        border-radius: 0 !important;
        outline: 0 !important;
        box-shadow: none !important;
        background: transparent !important;
        background-image: none !important;
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
    body.pds-admin .pds-profile-menu__logout-btn:hover {
        background: rgba(234, 88, 12, 0.08) !important;
        color: #9a3412 !important;
    }

    /* Page + cash payout table scroll safety net */
    html {
        overflow-y: auto !important;
        height: auto !important;
    }
    body.pds-admin,
    body.pds-admin#app {
        overflow-y: auto !important;
        height: auto !important;
        max-height: none !important;
    }
    body.pds-admin .content-page,
    body.pds-admin .content-page.pds-content-shell {
        overflow: visible !important;
        max-height: none !important;
        height: auto !important;
    }
    body.pds-admin .pds-rider-table-shell--scroll,
    body.pds-admin .pds-cash-payout-shell {
        overflow: auto !important;
        max-height: calc(100vh - 260px) !important;
        -webkit-overflow-scrolling: touch;
    }
</style>
@if(isset($assets) && in_array('phone', $assets))
    <link rel="stylesheet" href="{{ asset('vendor/intlTelInput/css/intlTelInput.css') }}">
@endif
<link rel="stylesheet" href="{{ asset('css/sweetalert2.min.css') }}">
