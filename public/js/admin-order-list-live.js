/* Deprecated shim — use admin-live.js */
(function (window) {
    'use strict';
    if (typeof window.bootAdminOrderListLiveRefresh !== 'function' && typeof window.bootAdminPageLiveRefresh === 'function') {
        window.bootAdminOrderListLiveRefresh = window.bootAdminPageLiveRefresh;
    }
})(window);
