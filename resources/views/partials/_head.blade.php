<link rel="shortcut icon" class="site_favicon_preview" href="{{ getSingleMedia(appSettingData('get'), 'site_favicon', null) }}" />
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">
@php
    $liveBrandHex = $themeColor ?? brandColorHex();
    $liveBrandRgb = $brandColorRgb ?? brandColorRgb();
    $liveFontFamily = $brandFontFamily ?? brandFontCssFamily();
    $liveFontPack = $brandFontPack ?? brandFontPack();
    $liveFontScale = brandFontSizeScale($liveFontPack['id'] ?? null);
@endphp
@if(!empty($liveFontPack['google']))
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family={{ $liveFontPack['google'] }}&family=Noto+Sans+Myanmar:wght@400;500;600;700&display=swap" rel="stylesheet">
@else
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Myanmar:wght@400;500;600;700&display=swap" rel="stylesheet">
@endif
<style id="pds-brand-fontface">
@font-face {
    font-family: 'Z17Strength';
    src: url('{{ asset('fonts/brand/Z17Strength-Regular.ttf') }}') format('truetype');
    font-weight: 400;
    font-style: normal;
    font-display: swap;
}
@font-face {
    font-family: 'Z17Strength';
    src: url('{{ asset('fonts/brand/Z17Strength-Bold.ttf') }}') format('truetype');
    font-weight: 600;
    font-style: normal;
    font-display: swap;
}
@font-face {
    font-family: 'Z17Strength';
    src: url('{{ asset('fonts/brand/Z17Strength-Bold.ttf') }}') format('truetype');
    font-weight: 700;
    font-style: normal;
    font-display: swap;
}
@font-face {
    font-family: 'Z17-Strength';
    src: url('{{ asset('fonts/brand/Z17Strength-Regular.ttf') }}') format('truetype');
    font-weight: 400;
    font-style: normal;
    font-display: swap;
}
@font-face {
    font-family: 'Z17-Strength';
    src: url('{{ asset('fonts/brand/Z17Strength-Bold.ttf') }}') format('truetype');
    font-weight: 600;
    font-style: normal;
    font-display: swap;
}
@font-face {
    font-family: 'Z17-Strength';
    src: url('{{ asset('fonts/brand/Z17Strength-Bold.ttf') }}') format('truetype');
    font-weight: 700;
    font-style: normal;
    font-display: swap;
}
@font-face {
    font-family: 'Z17 Strength';
    src: url('{{ asset('fonts/brand/Z17Strength-Regular.ttf') }}') format('truetype');
    font-weight: 400;
    font-style: normal;
    font-display: swap;
}
@font-face {
    font-family: 'Z17 Strength';
    src: url('{{ asset('fonts/brand/Z17Strength-Bold.ttf') }}') format('truetype');
    font-weight: 600;
    font-style: normal;
    font-display: swap;
}
@font-face {
    font-family: 'Z17 Strength';
    src: url('{{ asset('fonts/brand/Z17Strength-Bold.ttf') }}') format('truetype');
    font-weight: 700;
    font-style: normal;
    font-display: swap;
}
:root {
    --admin-font: {!! $liveFontFamily !!};
    --admin-font-scale: {{ $liveFontScale }};
    --brand-rgb: {{ $liveBrandRgb }};
}
</style>
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
        public_css_inline('css/admin-themes.css')."\n".
        public_css_inline('css/pds-layout.css')
    );
@endphp
@if($pdsInlineCss !== '')
    <style id="pds-admin-theme-inline">{!! $pdsInlineCss !!}</style>
@else
    <link rel="stylesheet" href="{{ public_asset_ver('css/custom.css') }}">
    <link rel="stylesheet" href="{{ public_asset_ver('css/admin-dashboard-theme.css') }}">
    <link rel="stylesheet" href="{{ public_asset_ver('css/admin-themes.css') }}">
    <link rel="stylesheet" href="{{ public_asset_ver('css/pds-layout.css') }}">
@endif
<link rel="stylesheet" href="{{ public_asset_ver('css/image-drop-upload.css') }}">
{{-- MUST load AFTER theme CSS so brand color/font win over Outfit / FE6F07 defaults. --}}
<style id="pds-brand-live">
:root,
html,
body.pds-admin {
    --site-color: {{ $liveBrandHex }} !important;
    --primary: {{ $liveBrandHex }} !important;
    --blue: {{ $liveBrandHex }} !important;
    --info: {{ $liveBrandHex }} !important;
    --admin-primary: {{ $liveBrandHex }} !important;
    --pds-primary: {{ $liveBrandHex }} !important;
    --pds-accent: {{ $liveBrandHex }} !important;
    --brand-rgb: {{ $liveBrandRgb }} !important;
    --admin-primary-light: rgba({{ $liveBrandRgb }}, 0.12) !important;
    --admin-primary-soft: rgba({{ $liveBrandRgb }}, 0.08) !important;
    --admin-font: {!! $liveFontFamily !!} !important;
    --admin-font-scale: {{ $liveFontScale }} !important;
}
body.pds-admin {
    font-family: var(--admin-font) !important;
    font-size: calc(1rem * var(--admin-font-scale, 1)) !important;
}
body.pds-admin #app,
body.pds-admin .wrapper,
body.pds-admin .content-page,
body.pds-admin .pds-page-wrap,
body.pds-admin .mm-content,
body.pds-admin .container-fluid,
body.pds-admin h1,
body.pds-admin h2,
body.pds-admin h3,
body.pds-admin h4,
body.pds-admin h5,
body.pds-admin h6,
body.pds-admin p,
body.pds-admin span:not(.fa):not(.fas):not(.far):not(.fab):not(.fal):not([class*="fa-"]):not([class*="ri-"]),
body.pds-admin a:not(.fa):not(.fas):not(.far):not(.fab):not([class*="fa-"]):not([class*="ri-"]),
body.pds-admin label,
body.pds-admin li,
body.pds-admin td,
body.pds-admin th,
body.pds-admin button,
body.pds-admin input,
body.pds-admin select,
body.pds-admin textarea,
body.pds-admin .btn,
body.pds-admin .nav,
body.pds-admin .table,
body.pds-admin .form-control,
body.pds-admin .card,
body.pds-admin .dropdown-menu,
body.pds-admin .mm-sidebar,
body.pds-admin .pds-sidebar,
body.pds-admin .badge,
body.pds-admin .dataTables_wrapper {
    font-family: var(--admin-font) !important;
}
body.pds-admin .btn-primary,
body.pds-admin .btn-warning,
body.pds-admin .btn-info,
body.pds-admin .bg-primary,
body.pds-admin .badge-primary,
body.pds-admin .badge.bg-primary,
body.pds-admin .pds-dispatch-filter-action-primary,
body.pds-admin .pds-daily-check-search-btn {
    background-color: {{ $liveBrandHex }} !important;
    background-image: none !important;
    border-color: {{ $liveBrandHex }} !important;
    color: #fff !important;
}
body.pds-admin .btn-primary,
body.pds-admin .btn-primary span,
body.pds-admin .btn-primary i,
body.pds-admin .btn-warning,
body.pds-admin .btn-warning span,
body.pds-admin .btn-warning i,
body.pds-admin .btn-info,
body.pds-admin .btn-info span,
body.pds-admin .btn-info i,
body.pds-admin .bg-primary,
body.pds-admin .bg-primary span,
body.pds-admin .badge-primary,
body.pds-admin .badge.bg-primary,
body.pds-admin .pds-dispatch-filter-action-primary,
body.pds-admin .pds-dispatch-filter-action-primary span,
body.pds-admin .pds-dispatch-filter-action-primary i,
body.pds-admin .pds-daily-check-search-btn,
body.pds-admin .pds-daily-check-search-btn span,
body.pds-admin .pds-daily-check-search-btn i,
body.pds-admin .pds-rider-check-btn,
body.pds-admin .pds-rider-check-btn span,
body.pds-admin .pds-rider-check-btn i,
body.pds-admin .pds-rider-of-month-btn,
body.pds-admin .pds-rider-of-month-btn span,
body.pds-admin .pds-rider-of-month-btn i,
body.pds-admin .pds-dispatch-btn-primary,
body.pds-admin .pds-dispatch-btn-primary span,
body.pds-admin .pds-dispatch-btn-primary .pds-dispatch-btn-label,
body.pds-admin .pds-assign-action-btn:not(.pds-assign-action-btn--kyo-shin),
body.pds-admin .pds-assign-action-btn:not(.pds-assign-action-btn--kyo-shin) span,
body.pds-admin .pds-assign-action-btn:not(.pds-assign-action-btn--kyo-shin) i,
body.pds-admin .pds-assign-action-btn:not(.pds-assign-action-btn--kyo-shin) .pds-assign-action-btn__label,
body.pds-admin .pds-hr-stat:not(.pds-hr-stat--soft),
body.pds-admin .pds-hr-stat:not(.pds-hr-stat--soft) span,
body.pds-admin .pds-os-settlement-tab.is-active,
body.pds-admin .pds-os-settlement-tab.is-active span,
body.pds-admin .pds-os-settlement-tab.is-active i {
    color: #fff !important;
}
body.pds-admin .pds-os-settlement-tab.is-active em {
    color: var(--site-color, {{ $liveBrandHex }}) !important;
}
body.pds-admin .text-primary,
body.pds-admin a.text-primary,
body.pds-admin .pds-dispatch-row-actions a,
body.pds-admin .pds-dispatch-action-edit,
body.pds-admin .pds-dispatch-action-print,
body.pds-admin .pds-dispatch-action-cancel {
    color: {{ $liveBrandHex }} !important;
}
body.pds-admin .pds-sidebar .badge-pill,
body.pds-admin .pds-sidebar .badge-primary,
body.pds-admin .pds-sidebar .badge-info,
body.pds-admin .pds-sidebar .badge-warning,
body.pds-admin .pds-sidebar .badge-danger,
body.pds-admin .pds-dm-branch-tab.is-active .pds-dm-branch-tab__count,
body.pds-admin .pds-dm-branch-tab.is-active .badge,
body.pds-admin .pds-assign-100-tab.is-active em,
body.pds-admin .pds-dispatch-items-tab.is-active em {
    background: {{ $liveBrandHex }} !important;
    background-color: {{ $liveBrandHex }} !important;
    color: #fff !important;
}
body.pds-admin .pds-dm-branch-tab.is-active,
body.pds-admin .pds-assign-100-tab.is-active,
body.pds-admin .pds-dispatch-items-tab.is-active {
    color: {{ $liveBrandHex }} !important;
}
body.pds-admin .pds-sidebar .mm-sidebar-menu .side-menu li.active > a,
body.pds-admin .pds-sidebar .mm-sidebar-menu .side-menu li.active-menu > a {
    border-left-color: {{ $liveBrandHex }} !important;
}
body.pds-admin #loading {
    background: #1A120E !important;
}
body.pds-admin .pds-splash__progress-bar,
body.pds-admin .pds-splash__track-bar {
    background: {{ $liveBrandHex }} !important;
}
body.pds-admin .pds-splash__ring--a {
    border-top-color: {{ $liveBrandHex }} !important;
    border-right-color: {{ $liveBrandHex }} !important;
}
body.pds-admin .pds-splash__title,
body.pds-admin .pds-splash__subtitle {
    font-family: var(--admin-font) !important;
}
</style>
<script>
window.pdsBrandColor = @json($liveBrandHex);
window.pdsBrandRgb = @json($liveBrandRgb);
window.pdsBrandFontFamily = @json($liveFontFamily);
window.pdsBrandFontId = @json($liveFontPack['id'] ?? brandFontId());
document.documentElement.setAttribute('data-brand-font', window.pdsBrandFontId || '');
</script>
<style id="pds-hide-scrollbars">
    /*
     * Page chrome: keep document from growing sideways.
     * Do NOT zero-out scrollbars globally — that breaks horizontal drag-scroll
     * on Windows / some touch laptops (wide DataTables / table-responsive).
     */
    html.pds-admin-root {
        overflow-x: hidden !important;
    }

    /*
     * Wide tables: scroll INSIDE a height-capped shell so thead/tfoot can freeze.
     * Footer docks outside the shell (e.g. .pds-items-dock) stay put automatically.
     */
    body.pds-admin .table-responsive,
    body.pds-admin .dataTables_scroll,
    body.pds-admin .dataTables_scrollBody,
    body.pds-admin .pds-rider-table-shell--scroll,
    body.pds-admin .pds-cash-payout-shell.pds-rider-table-shell--scroll,
    body.pds-admin .pds-dispatch-to-assign-table-shell,
    body.pds-admin .pds-table-scroll,
    body.pds-admin .pds-h-scroll {
        overflow-x: auto !important;
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin !important;
        -ms-overflow-style: auto !important;
    }

    /* DataTables scrollY/scrollX hosts — shell must not steal the scrollport
       (items list is excluded: it scrolls on the shell, not scrollBody). */
    body.pds-admin .pds-dispatch-table-shell,
    body.pds-admin .pds-dispatch-list-page .pds-dispatch-table-shell,
    body.pds-admin .pds-table-shell:has(.dataTables_scroll):not(.pds-dispatch-items-table-shell),
    body.pds-admin .pds-deliveryman-table-shell:has(.dataTables_scroll),
    body.pds-admin .pds-report-table-shell:has(.dataTables_scroll) {
        overflow: hidden !important;
        max-height: none !important;
    }

    body.pds-admin .pds-dt-scroll-host.table-responsive,
    body.pds-admin .dataTables_wrapper .table-responsive:has(.dataTables_scroll) {
        overflow: visible !important;
    }

    body.pds-admin .dataTables_wrapper .dataTables_scrollBody {
        overflow-x: auto !important;
        overflow-y: auto !important;
        scrollbar-width: thin !important;
        -ms-overflow-style: auto !important;
    }

    body.pds-admin .dataTables_wrapper .dataTables_scrollBody::-webkit-scrollbar {
        width: 10px !important;
        height: 10px !important;
        display: block !important;
    }

    body.pds-admin div.dataTables_scrollFoot.is-visible,
    body.pds-admin div.dataTables_scrollFootInner.is-visible {
        display: block !important;
        height: auto !important;
    }

    body.pds-admin .pds-rider-table-shell--scroll,
    body.pds-admin .pds-cash-payout-shell.pds-rider-table-shell--scroll,
    body.pds-admin .pds-dispatch-to-assign-table-shell {
        max-height: calc(100vh - var(--pds-topbar-h, 60px) - 12.5rem) !important;
    }

    /* Items list: natural height — page scrolls (no nested max-height). */
    body.pds-admin .pds-dispatch-items-page .pds-dispatch-items-table-shell,
    body.pds-admin .pds-dispatch-items-table-shell {
        max-height: none !important;
        height: auto !important;
        min-height: 0 !important;
        overflow-x: auto !important;
        overflow-y: visible !important;
    }

    body.pds-admin .pds-dispatch-items-screen,
    body.pds-admin .pds-dispatch-items-body {
        height: auto !important;
        min-height: 0 !important;
        max-height: none !important;
    }

    body.pds-admin .pds-dispatch-items-table-shell .dataTables_scroll,
    body.pds-admin .pds-dispatch-items-table-shell .dataTables_scrollBody,
    body.pds-admin .pds-dispatch-items-table-shell .dataTables_scrollHead,
    body.pds-admin .pds-dispatch-items-table-shell .dataTables_scrollFoot {
        height: auto !important;
        max-height: none !important;
        min-height: 0 !important;
        overflow: visible !important;
    }

    body.pds-admin .pds-dispatch-items-table-shell .dataTables_scrollFoot {
        display: none !important;
    }

    body.pds-admin .pds-dispatch-items-table-shell .pds-dispatch-items-datatable thead th {
        position: sticky !important;
        top: 0 !important;
        z-index: 6 !important;
    }

    body.pds-admin .table-responsive::-webkit-scrollbar,
    body.pds-admin .dataTables_scroll::-webkit-scrollbar,
    body.pds-admin .dataTables_scrollBody::-webkit-scrollbar,
    body.pds-admin .pds-dispatch-items-table-shell::-webkit-scrollbar,
    body.pds-admin .pds-rider-table-shell--scroll::-webkit-scrollbar,
    body.pds-admin .pds-dispatch-to-assign-table-shell::-webkit-scrollbar,
    body.pds-admin .pds-table-scroll::-webkit-scrollbar,
    body.pds-admin .pds-h-scroll::-webkit-scrollbar {
        width: 10px !important;
        height: 10px !important;
        display: block !important;
        appearance: auto !important;
        -webkit-appearance: auto !important;
    }

    body.pds-admin .table-responsive::-webkit-scrollbar-thumb,
    body.pds-admin .dataTables_scroll::-webkit-scrollbar-thumb,
    body.pds-admin .dataTables_scrollBody::-webkit-scrollbar-thumb,
    body.pds-admin .pds-dispatch-items-table-shell::-webkit-scrollbar-thumb,
    body.pds-admin .pds-rider-table-shell--scroll::-webkit-scrollbar-thumb,
    body.pds-admin .pds-dispatch-to-assign-table-shell::-webkit-scrollbar-thumb,
    body.pds-admin .pds-table-scroll::-webkit-scrollbar-thumb,
    body.pds-admin .pds-h-scroll::-webkit-scrollbar-thumb {
        background: rgba(15, 23, 42, 0.28) !important;
        border-radius: 999px !important;
        border: 2px solid transparent !important;
        background-clip: padding-box !important;
    }

    body.pds-admin .table-responsive::-webkit-scrollbar-track,
    body.pds-admin .dataTables_scroll::-webkit-scrollbar-track,
    body.pds-admin .dataTables_scrollBody::-webkit-scrollbar-track,
    body.pds-admin .pds-dispatch-items-table-shell::-webkit-scrollbar-track,
    body.pds-admin .pds-rider-table-shell--scroll::-webkit-scrollbar-track,
    body.pds-admin .pds-dispatch-to-assign-table-shell::-webkit-scrollbar-track,
    body.pds-admin .pds-table-scroll::-webkit-scrollbar-track,
    body.pds-admin .pds-h-scroll::-webkit-scrollbar-track {
        background: rgba(15, 23, 42, 0.06) !important;
    }

    /* Non-DataTables shells: CSS sticky freeze */
    body.pds-admin .pds-plain-freeze:not(.pds-dispatch-items-table-shell),
    body.pds-admin .pds-auto-freeze-shell,
    body.pds-admin .pds-table-shell.pds-plain-freeze:not(.pds-dispatch-items-table-shell),
    body.pds-admin .pds-rider-table-shell--scroll,
    body.pds-admin .pds-dispatch-to-assign-table-shell:not(:has(.dataTables_scroll)) {
        overflow-x: auto !important;
        overflow-y: auto !important;
        max-height: calc(100vh - var(--pds-topbar-h, 60px) - 12.5rem) !important;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin !important;
        -ms-overflow-style: auto !important;
    }

    /* Items list must win over plain-freeze (page scrolls; no nested viewport). */
    body.pds-admin .pds-dispatch-items-table-shell,
    body.pds-admin .pds-dispatch-items-page .pds-dispatch-items-table-shell,
    body.pds-admin .pds-dispatch-items-table-shell.pds-plain-freeze {
        max-height: none !important;
        height: auto !important;
        overflow-x: auto !important;
        overflow-y: visible !important;
    }

    /* Status pills must not clip inside tight spreadsheet cells */
    body.pds-admin table tbody td:has(.pds-dispatch-status),
    body.pds-admin table tbody td:has(.pds-dispatch-item-status),
    body.pds-admin table tbody td:has(.pds-rider-status-chip) {
        overflow: visible !important;
        max-width: none !important;
        white-space: nowrap !important;
    }
    body.pds-admin .pds-dispatch-to-assign-table th:nth-child(6),
    body.pds-admin .pds-dispatch-to-assign-table td:nth-child(6) {
        width: 128px !important;
        min-width: 128px !important;
        max-width: none !important;
        overflow: visible !important;
    }

    body.pds-admin .pds-plain-freeze thead th,
    body.pds-admin .pds-plain-freeze thead td,
    body.pds-admin .pds-rider-table-shell--scroll thead th,
    body.pds-admin .pds-rider-items-table thead th,
    body.pds-admin .pds-rider-list-table thead th,
    body.pds-admin .pds-dispatch-items-table-shell:not(:has(.dataTables_scroll)) thead th,
    body.pds-admin .pds-dispatch-to-assign-table thead th,
    body.pds-admin .pds-dispatch-table thead th {
        position: sticky !important;
        top: 0 !important;
        z-index: 8 !important;
    }

    body.pds-admin .pds-plain-freeze tfoot td,
    body.pds-admin .pds-plain-freeze tfoot th,
    body.pds-admin .pds-rider-items-table tfoot td,
    body.pds-admin .pds-rider-items-table tfoot th,
    body.pds-admin .pds-rider-table-shell--scroll tfoot td,
    body.pds-admin .pds-rider-table-shell--scroll tfoot th {
        position: sticky !important;
        bottom: 0 !important;
        z-index: 7 !important;
        background: #fff7ed !important;
    }

    /* Beat spreadsheet collapse + backend.css .table{overflow:hidden} so sticky works */
    body.pds-admin .content-page .pds-plain-freeze table.table,
    body.pds-admin .content-page .pds-plain-freeze table.dataTable,
    body.pds-admin .content-page .pds-plain-freeze .pds-rider-list-table,
    body.pds-admin .content-page .pds-plain-freeze .pds-rider-items-table,
    body.pds-admin .content-page .pds-plain-freeze .pds-dispatch-to-assign-table,
    body.pds-admin .content-page .pds-rider-table-shell--scroll table.table,
    body.pds-admin .content-page .pds-rider-table-shell--scroll .pds-rider-items-table,
    body.pds-admin .content-page .pds-rider-table-shell--scroll .pds-rider-list-table,
    body.pds-admin .pds-plain-freeze > table,
    body.pds-admin .pds-plain-freeze table,
    body.pds-admin table.pds-sticky-freeze {
        border-collapse: separate !important;
        border-spacing: 0 !important;
        overflow: visible !important;
    }

    /* List pages: fit table to card width (inner scrollport still scrolls) */
    body.pds-admin .pds-deliveryman-list-page table.dataTable,
    body.pds-admin .pds-os-list-page table.dataTable,
    body.pds-admin .pds-user-reg-page table.dataTable {
        width: 100% !important;
        max-width: 100% !important;
        min-width: 0 !important;
    }
</style>
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
        color: #dc2626 !important;
        cursor: pointer !important;
        text-align: left !important;
    }
</style>
@if(isset($assets) && in_array('phone', $assets))
    <link rel="stylesheet" href="{{ public_asset_ver('vendor/intlTelInput/css/intlTelInput.css') }}">
@endif
<link rel="stylesheet" href="{{ public_asset_ver('css/sweetalert2.min.css') }}">
