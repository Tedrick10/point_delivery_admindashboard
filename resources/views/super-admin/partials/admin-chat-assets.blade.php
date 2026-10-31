{{-- Admin chat CSS/JS for Inquiry & Complaint pages hosted inside Super Admin chrome --}}
<link rel="stylesheet" href="{{ public_asset_ver('css/backend-bundle.min.css') }}">
<link rel="stylesheet" href="{{ public_asset_ver('css/backend.css') }}">
<link rel="stylesheet" href="{{ public_asset_ver('css/custom.css') }}">
<link rel="stylesheet" href="{{ public_asset_ver('css/admin-dashboard-theme.css') }}">
<link rel="stylesheet" href="{{ public_asset_ver('css/admin-themes.css') }}">
<link rel="stylesheet" href="{{ public_asset_ver('css/pds-layout.css') }}">
<style>
    /* Chat-mode loads admin theme after SA CSS — keep badge digits white on red. */
    body.sa-panel.sa-chat-mode .sa-topbar-chat__badge {
        background: #EF4444 !important;
        color: #fff !important;
        -webkit-text-fill-color: #fff !important;
    }
    body.sa-panel.sa-chat-mode .sa-topbar-chat__badge.d-none {
        display: none !important;
    }
    /* Keep chat pages readable inside SA content shell */
    body.sa-panel.sa-chat-mode .sa-content {
        padding-bottom: 0.75rem;
    }
    body.sa-panel.sa-chat-mode .sa-pagehead {
        display: none;
    }
    body.sa-panel.sa-chat-mode .pds-support-page,
    body.sa-panel.sa-chat-mode .pds-dispatch-assigned-items-page {
        max-width: none;
    }
    body.sa-panel.sa-chat-mode .content-page,
    body.sa-panel.sa-chat-mode .pds-content-shell {
        padding: 0 !important;
        margin: 0 !important;
        height: auto !important;
        max-height: none !important;
        overflow: visible !important;
        min-height: 0 !important;
    }
    body.sa-panel.sa-chat-mode .pds-support-page {
        min-height: calc(100dvh - 7.5rem);
        height: calc(100dvh - 7.5rem);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    body.sa-panel.sa-chat-mode .pds-support-layout {
        flex: 1 1 auto;
        min-height: 0;
    }
    body.sa-panel.sa-chat-mode .pds-os-profile-page {
        background: transparent;
    }
</style>
