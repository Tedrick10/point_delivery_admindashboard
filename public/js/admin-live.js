(function (window, $) {
    'use strict';

    var DEFAULT_INTERVAL = 4000;

    function resolveDataTable(selector) {
        if (window.LaravelDataTables && window.LaravelDataTables['dataTableBuilder']) {
            return window.LaravelDataTables['dataTableBuilder'];
        }
        var keys = window.LaravelDataTables ? Object.keys(window.LaravelDataTables) : [];
        if (keys.length && window.LaravelDataTables[keys[0]]) {
            return window.LaravelDataTables[keys[0]];
        }
        var $table = $(selector || '.pds-dispatch-datatable, .pds-dispatch-items-datatable, .pds-datatable, table.dataTable').first();
        if ($table.length && $.fn.DataTable && $.fn.DataTable.isDataTable($table)) {
            return $table.DataTable();
        }
        return null;
    }

    function setBadge($el, count, classes) {
        if (!$el || !$el.length) return;
        count = Number(count) || 0;
        var label = count >= 100 ? '99+' : String(count);
        if (count > 0) {
            $el.text(label)
                .removeClass('d-none')
                .addClass(classes || 'badge badge-pill p-1');
        } else {
            $el.text('')
                .addClass('d-none')
                .removeClass('animate__animated animate__flash');
        }
    }

    function applySidebarBadges(badges) {
        if (!badges) return;
        setBadge($('#assign100Count'), badges.assign100, 'badge badge-pill badge-warning p-1 animate__animated animate__flash');
        setBadge($('#preOrderCount'), badges.preOrder, 'badge badge-pill badge-info p-1 animate__animated animate__flash');
        setBadge($('#assignedItemListCount'), badges.assignedItems, 'badge badge-pill badge-info p-1');
        setBadge($('#pickupErrorCount'), badges.pickupError, 'badge badge-pill badge-danger p-1 animate__animated animate__flash');
        setBadge($('#pickupCancelledCount'), badges.pickupCancelled, 'badge badge-pill badge-danger p-1 animate__animated animate__flash');
        setBadge($('#hubInboxCount'), badges.hubInbox, 'badge badge-pill badge-info p-1');
        setBadge($('#requestCount'), badges.requestCount || badges.clientPendingOrders, 'badge badge-pill badge-primary p-1 mr-3 animate__animated animate__flash');
        setBadge($('#clientWithdrawCount'), badges.clientWithdraw, 'badge badge-pill badge-primary p-1 mr-3 animate__animated animate__flash');
        if (badges.hubInbound && typeof badges.hubInbound === 'object') {
            Object.keys(badges.hubInbound).forEach(function (hubId) {
                setBadge($('#hubInboundCount-' + hubId), badges.hubInbound[hubId], 'badge badge-pill badge-info p-1');
            });
        }
    }

    function applyNotificationCount(count) {
        count = Number(count) || 0;
        var $badge = $('.pds-topbar-notify .notify_count, .notify_count').first();
        if (!$badge.length) return;
        if (count > 0) {
            $badge.addClass('notification_tag').text(count >= 100 ? '99+' : String(count)).removeClass('d-none');
        } else {
            $badge.addClass('d-none').removeClass('notification_tag').text('');
        }
    }

    function applyDashboardStats(stats) {
        if (!stats) return;
        $('[data-live-stat]').each(function () {
            var key = $(this).attr('data-live-stat');
            if (!key || typeof stats[key] === 'undefined') return;
            $(this).text(stats[key]);
        });
    }

    function hardNavigateFallback(url) {
        if (typeof window.pdsAdminReload === 'function' && !url) {
            window.pdsAdminReload();
            return;
        }
        if (typeof window.pdsAdminGo === 'function' && url) {
            window.pdsAdminGo(url);
            return;
        }
        if (window.AdminSpa && typeof window.AdminSpa.reload === 'function' && !url) {
            window.AdminSpa.reload();
            return;
        }
        if (url) window.location.assign(url);
        else if (typeof window.pdsAdminReload === 'function') window.pdsAdminReload();
    }

    function destroyDataTablesIn($scope) {
        $scope = $scope && $scope.length ? $scope : $(document);
        if (window.LaravelDataTables && typeof window.LaravelDataTables === 'object') {
            Object.keys(window.LaravelDataTables).forEach(function (key) {
                try {
                    var api = window.LaravelDataTables[key];
                    if (api && typeof api.destroy === 'function') {
                        api.destroy(true);
                    }
                } catch (e) {}
            });
            window.LaravelDataTables = {};
        }
        try {
            $scope.find('table').addBack('table').each(function () {
                if ($.fn.DataTable && $.fn.DataTable.isDataTable(this)) {
                    $(this).DataTable().destroy(true);
                }
            });
        } catch (e) {}
    }

    function softReplaceLiveRoot() {
        var $root = $('#adminLiveRoot');
        if (!$root.length) return false;
        if ($('input:focus, textarea:focus, select:focus, .select2-container--open').length) return true;
        if ($('.modal.show, .jconfirm').length) return true;

        $.ajax({
            url: window.location.href,
            method: 'GET',
            dataType: 'html',
            cache: false,
            headers: { 'X-Admin-Live-Fragment': '1' }
        }).done(function (html) {
            var doc = new DOMParser().parseFromString(html, 'text/html');
            var newRoot = doc.querySelector('#adminLiveRoot');
            if (newRoot) {
                // Soft HTML replace without destroy leaves DataTables registered → tn/3 on next init.
                destroyDataTablesIn($root);
                var imported = document.importNode(newRoot, true);
                imported.querySelectorAll('script').forEach(function (s) { s.remove(); });
                $root[0].replaceWith(imported);
                $(document).trigger('admin-live:replaced');
            } else if (typeof window.pdsAdminReload === 'function') {
                window.pdsAdminReload();
            } else if (window.AdminSpa && typeof window.AdminSpa.reload === 'function') {
                window.AdminSpa.reload();
            }
        }).fail(function () {
            if (typeof window.pdsAdminReload === 'function') {
                window.pdsAdminReload();
            } else if (window.AdminSpa && typeof window.AdminSpa.reload === 'function') {
                window.AdminSpa.reload();
            }
        });
        return true;
    }

    function reloadPageContent(options) {
        options = options || {};
        if (typeof options.onReload === 'function') {
            options.onReload();
            return;
        }
        if (typeof window.reloadDispatchItemsTable === 'function' && options.useItemsReload) {
            window.reloadDispatchItemsTable();
            return;
        }
        var table = resolveDataTable(options.tableSelector || '');
        if (table && table.ajax && typeof table.ajax.reload === 'function') {
            if (window.__adminLiveReloading) return;
            window.__adminLiveReloading = true;
            table.ajax.reload(function () {
                window.__adminLiveReloading = false;
            }, false);
            return;
        }
        if (softReplaceLiveRoot()) return;
        if (options.hardReloadFallback) {
            hardNavigateFallback();
        }
    }

    window.bootAdminOrderListLiveRefresh = function (options) {
        return window.bootAdminPageLiveRefresh(options);
    };

    window.bootAdminPageLiveRefresh = function (options) {
        options = options || {};
        var url = options.url;
        var intervalMs = options.intervalMs || DEFAULT_INTERVAL;
        if (!url) return null;

        if (window.__pdsPageLiveHandle && typeof window.__pdsPageLiveHandle.stop === 'function') {
            try { window.__pdsPageLiveHandle.stop(); } catch (e) {}
        }

        var lastVersion = null;
        var inFlight = false;
        var timer = null;

        function poll() {
            if (document.hidden || inFlight) return;
            inFlight = true;
            $.ajax({
                url: url,
                method: 'GET',
                dataType: 'json',
                cache: false,
                data: options.data || {}
            }).done(function (res) {
                var version = res && res.version ? String(res.version) : null;
                if (!version) return;
                if (lastVersion === null) {
                    lastVersion = version;
                    return;
                }
                if (version !== lastVersion) {
                    lastVersion = version;
                    reloadPageContent(options);
                }
            }).fail(function (xhr) {
                if (window.console && console.debug) {
                    console.debug('[admin-live] page-version failed', xhr && xhr.status);
                }
            }).always(function () {
                inFlight = false;
            });
        }

        function start() {
            if (timer) return;
            poll();
            timer = window.setInterval(poll, intervalMs);
        }

        function stop() {
            if (!timer) return;
            window.clearInterval(timer);
            timer = null;
        }

        document.addEventListener('visibilitychange', function onVis() {
            if (document.hidden) stop();
            else start();
        });

        start();
        window.__pdsPageLiveBooted = true;
        window.__pdsPageLiveHandle = { start: start, stop: stop, poll: poll };
        return window.__pdsPageLiveHandle;
    };

    /**
     * Single global poller: badges + notifications + dashboard + content refresh.
     */
    window.bootAdminGlobalLive = function (options) {
        options = options || {};
        var stateUrl = options.stateUrl;
        var intervalMs = options.intervalMs || DEFAULT_INTERVAL;
        if (!stateUrl) return null;

        window.__pdsGlobalLiveOptions = $.extend({}, window.__pdsGlobalLiveOptions || {}, options);

        // Avoid double-boot across scripts / SPA navigations.
        if (window.__pdsGlobalLiveBooted) {
            if (typeof window.adminLiveRefreshNow === 'function') {
                window.adminLiveRefreshNow();
            }
            return window.__pdsGlobalLiveHandle || null;
        }
        window.__pdsGlobalLiveBooted = true;

        var inFlight = false;
        var timer = null;
        var lastVersion = null;

        function currentOptions() {
            return window.__pdsGlobalLiveOptions || options;
        }

        function poll() {
            if (document.hidden || inFlight) return;
            inFlight = true;
            var opts = currentOptions();
            var data = $.extend({}, opts.data || {});
            if (opts.page) data.page = opts.page;
            if (opts.includeDashboard) data.include_dashboard = 1;

            $.ajax({
                url: opts.stateUrl || stateUrl,
                method: 'GET',
                dataType: 'json',
                cache: false,
                data: data
            }).done(function (res) {
                if (!res) return;
                applySidebarBadges(res.badges);
                if (res.notifications) {
                    applyNotificationCount(res.notifications.counts);
                }
                if (res.dashboard) {
                    applyDashboardStats(res.dashboard);
                }

                var version = res.version ? String(res.version) : null;
                if (!version) return;
                if (lastVersion === null || opts.__resetVersion) {
                    lastVersion = version;
                    opts.__resetVersion = false;
                    return;
                }
                if (version !== lastVersion) {
                    lastVersion = version;
                    if (opts.refreshContent !== false) {
                        reloadPageContent({
                            tableSelector: opts.tableSelector || '',
                            useItemsReload: opts.useItemsReload,
                            onReload: opts.onReload,
                            hardReloadFallback: !!opts.hardReloadFallback
                        });
                    }
                }
            }).fail(function (xhr) {
                if (window.console && console.debug) {
                    console.debug('[admin-live] state failed', xhr && xhr.status);
                }
            }).always(function () {
                inFlight = false;
            });
        }

        function start() {
            if (timer) return;
            poll();
            timer = window.setInterval(poll, intervalMs);
        }

        function stop() {
            if (!timer) return;
            window.clearInterval(timer);
            timer = null;
        }

        document.addEventListener('visibilitychange', function () {
            if (document.hidden) stop();
            else start();
        });

        start();
        window.adminLiveRefreshNow = poll;
        window.__pdsGlobalLiveHandle = { start: start, stop: stop, poll: poll };
        return window.__pdsGlobalLiveHandle;
    };

    window.adminLiveUpdateOptions = function (partial) {
        partial = partial || {};
        window.__pdsGlobalLiveOptions = $.extend({}, window.__pdsGlobalLiveOptions || {}, partial, {
            __resetVersion: true
        });
    };

    window.adminLiveReloadPage = function () {
        var table = resolveDataTable('');
        if (table && table.ajax && typeof table.ajax.reload === 'function') {
            table.ajax.reload(null, false);
            return;
        }
        if (typeof window.reloadDispatchItemsTable === 'function') {
            window.reloadDispatchItemsTable();
            return;
        }
        if (softReplaceLiveRoot()) return;
        if (window.AdminSpa && typeof window.AdminSpa.reload === 'function') {
            window.AdminSpa.reload();
            return;
        }
        if (typeof window.adminLiveRefreshNow === 'function') {
            window.adminLiveRefreshNow();
        }
    };

    window.adminLiveApplyBadges = applySidebarBadges;
    window.adminLiveApplyDashboard = applyDashboardStats;
    window.adminLiveSoftReplace = softReplaceLiveRoot;
})(window, jQuery);
