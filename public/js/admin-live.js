(function (window, $) {
    'use strict';

    var DEFAULT_INTERVAL = 4000;

    function dataTableNode(api) {
        try {
            return api && typeof api.table === 'function' ? api.table().node() : null;
        } catch (e) {
            return null;
        }
    }

    function dataTableIsOnPage(api) {
        var node = dataTableNode(api);
        return !!(node && document.contains(node));
    }

    function dataTableInsideLiveRoot(api) {
        var root = document.getElementById('adminLiveRoot');
        var node = dataTableNode(api);
        return !!(root && node && root.contains(node));
    }

    function resolveDataTable(selector) {
        if (window.LaravelDataTables && window.LaravelDataTables['dataTableBuilder']) {
            var builder = window.LaravelDataTables['dataTableBuilder'];
            if (dataTableIsOnPage(builder)) return builder;
        }
        var keys = window.LaravelDataTables ? Object.keys(window.LaravelDataTables) : [];
        for (var i = 0; i < keys.length; i++) {
            var api = window.LaravelDataTables[keys[i]];
            if (api && dataTableIsOnPage(api)) return api;
        }
        var $table = $(selector || '.pds-dispatch-datatable, .pds-dispatch-items-datatable, .pds-datatable, table.dataTable').first();
        if ($table.length && $.fn.DataTable && $.fn.DataTable.isDataTable($table)) {
            return $table.DataTable();
        }
        return null;
    }

    function collectPageData() {
        var data = {};
        var names = ['from_date', 'to_date', 'search_term', 'dispatch_status', 'branch_id', 'os_id', 'rider_id', 'city_id', 'country_id'];
        names.forEach(function (name) {
            var $el = $('#' + name);
            if (!$el.length) $el = $('[name="' + name + '"]').first();
            if ($el.length) {
                var val = $el.val();
                if (val !== undefined && val !== null && String(val) !== '') {
                    data[name] = val;
                }
            }
        });
        return data;
    }

    function applyOrderListTabs(counts) {
        if (!counts) return;
        Object.keys(counts).forEach(function (key) {
            var $n = $('.pds-dispatch-tab-count[data-live-tab="' + key + '"]');
            if (!$n.length) return;
            var n = Number(counts[key]) || 0;
            $n.text(String(n)).attr('aria-label', String(n));
        });
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
        var hasLiveRoot = !!document.getElementById('adminLiveRoot');
        var tableOnPage = table && table.ajax && typeof table.ajax.reload === 'function' && dataTableIsOnPage(table);
        var tableOwnsThisPage = tableOnPage && (!hasLiveRoot || dataTableInsideLiveRoot(table));
        if (tableOwnsThisPage) {
            if (window.__adminLiveReloading) return;
            window.__adminLiveReloading = true;
            table.ajax.reload(function () {
                window.__adminLiveReloading = false;
            }, false);
            return;
        }
        if (hasLiveRoot && softReplaceLiveRoot()) return;
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
            var data = $.extend({}, collectPageData(), opts.data || {});
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
                if (res.order_list_tabs) {
                    applyOrderListTabs(res.order_list_tabs);
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
        reloadPageContent({});
    };

    window.adminLiveCollectPageData = collectPageData;
    window.adminLiveApplyBadges = applySidebarBadges;
    window.adminLiveApplyDashboard = applyDashboardStats;
    window.adminLiveSoftReplace = softReplaceLiveRoot;
})(window, jQuery);
