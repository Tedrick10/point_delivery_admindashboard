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
        setBadge($('#assignedItemListCount'), badges.assignedItems, 'pds-topbar-badge');
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
        var $badge = $('.pds-topbar-notify .notify_count').first();
        if ($badge.length) {
            if (count > 0) {
                $badge.addClass('notification_tag').text(count >= 100 ? '99+' : String(count)).removeClass('d-none');
            } else {
                $badge.addClass('d-none').removeClass('notification_tag').text('');
            }
        }
        if (typeof window.updateNotificationDropdownMeta === 'function'
            && !$('.pds-notify-menu').hasClass('show')) {
            // Keep closed-dropdown header count in sync without rewriting unread sentence mid-open.
            var $headerCount = $('.pds-notify-header-count');
            $headerCount.text(count);
            if (count > 0) {
                $headerCount.removeClass('d-none');
            } else {
                $headerCount.addClass('d-none');
            }
        }
    }
    window.applyNotificationCount = applyNotificationCount;

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

    var __adminLiveLastUserScrollAt = 0;
    var __adminLiveScrollListening = false;

    function ensureAdminLiveScrollGuard() {
        if (__adminLiveScrollListening) return;
        __adminLiveScrollListening = true;
        document.addEventListener('scroll', function () {
            __adminLiveLastUserScrollAt = Date.now();
        }, true);
        document.addEventListener('wheel', function () {
            __adminLiveLastUserScrollAt = Date.now();
        }, { capture: true, passive: true });
        document.addEventListener('touchmove', function () {
            __adminLiveLastUserScrollAt = Date.now();
        }, { capture: true, passive: true });
    }

    function userRecentlyScrolling(ms) {
        return (Date.now() - __adminLiveLastUserScrollAt) < (ms || 1200);
    }

    function cssPathFromRoot(el, root) {
        if (!el || !root || !root.contains(el)) return '';
        var parts = [];
        var cur = el;
        while (cur && cur !== root) {
            var parent = cur.parentElement;
            if (!parent) break;
            var idx = 1;
            var sib = cur;
            while ((sib = sib.previousElementSibling)) {
                if (sib.tagName === cur.tagName) idx += 1;
            }
            parts.unshift(cur.tagName.toLowerCase() + ':nth-of-type(' + idx + ')');
            cur = parent;
        }
        return parts.length ? ('#adminLiveRoot > ' + parts.join(' > ')) : '#adminLiveRoot';
    }

    function captureScrollPositions() {
        var winY = window.scrollY || window.pageYOffset || 0;
        var winX = window.scrollX || window.pageXOffset || 0;
        var items = [];
        var root = document.getElementById('adminLiveRoot');
        var seen = [];

        function pushEl(el) {
            if (!el || seen.indexOf(el) !== -1) return;
            var top = el.scrollTop || 0;
            var left = el.scrollLeft || 0;
            if (!top && !left) return;
            seen.push(el);
            items.push({
                id: el.id || '',
                path: root && root.contains(el) ? cssPathFromRoot(el, root) : '',
                scrollTop: top,
                scrollLeft: left
            });
        }

        if (root) {
            pushEl(root);
            var nodes = root.querySelectorAll('*');
            for (var i = 0; i < nodes.length; i++) {
                var el = nodes[i];
                if (el.scrollHeight > el.clientHeight + 1 || el.scrollWidth > el.clientWidth + 1) {
                    pushEl(el);
                }
            }
        }

        ['.content-page', '.content', '#content-page', '.iq-content', '.container-fluid', '.dataTables_scrollBody', '.table-responsive'].forEach(function (sel) {
            try {
                document.querySelectorAll(sel).forEach(pushEl);
            } catch (e) {}
        });

        return { winX: winX, winY: winY, items: items };
    }

    function restoreScrollPositions(state) {
        if (!state) return;
        function apply() {
            try {
                window.scrollTo(state.winX || 0, state.winY || 0);
                if (document.scrollingElement) {
                    document.scrollingElement.scrollTop = state.winY || 0;
                    document.scrollingElement.scrollLeft = state.winX || 0;
                }
            } catch (e) {}
            (state.items || []).forEach(function (item) {
                var el = null;
                if (item.id) {
                    el = document.getElementById(item.id);
                }
                if (!el && item.path) {
                    try { el = document.querySelector(item.path); } catch (e) {}
                }
                if (el) {
                    el.scrollTop = item.scrollTop || 0;
                    el.scrollLeft = item.scrollLeft || 0;
                }
            });
        }
        apply();
        requestAnimationFrame(function () {
            apply();
            requestAnimationFrame(apply);
        });
        // Tables / images may reflow after soft replace.
        window.setTimeout(apply, 50);
        window.setTimeout(apply, 200);
    }

    function liveRootSignature(root) {
        if (!root) return '';
        try {
            var clone = root.cloneNode(true);
            clone.querySelectorAll('script').forEach(function (s) { s.remove(); });
            return (clone.innerHTML || '')
                .replace(/name="_token"[^>]*>/gi, '')
                .replace(/value="[a-zA-Z0-9]{20,}"/g, 'value=""')
                .replace(/\s+/g, ' ')
                .trim();
        } catch (e) {
            return '';
        }
    }

    function softReplaceLiveRoot() {
        ensureAdminLiveScrollGuard();
        var $root = $('#adminLiveRoot');
        if (!$root.length) return false;
        if ($('input:focus, textarea:focus, select:focus, .select2-container--open').length) return true;
        if ($('.flatpickr-calendar.open').length) return true;
        if ($('.modal.show, .jconfirm').length) return true;
        // Don't yank the page while the user is still scrolling.
        if (userRecentlyScrolling(1200)) return true;
        if (window.__adminLiveSoftReplacing) return true;

        window.__adminLiveSoftReplacing = true;
        var scrollState = captureScrollPositions();

        $.ajax({
            url: window.location.href,
            method: 'GET',
            dataType: 'html',
            cache: false,
            headers: { 'X-Admin-Live-Fragment': '1' }
        }).done(function (html) {
            // User may have started scrolling while the fragment was loading.
            if (userRecentlyScrolling(800) || $('input:focus, textarea:focus, select:focus, .select2-container--open').length || $('.flatpickr-calendar.open').length || $('.modal.show, .jconfirm').length) {
                return;
            }
            var doc = new DOMParser().parseFromString(html, 'text/html');
            var newRoot = doc.querySelector('#adminLiveRoot');
            if (newRoot) {
                var oldSig = liveRootSignature($root[0]);
                var newSig = liveRootSignature(newRoot);
                if (oldSig && newSig && oldSig === newSig) {
                    return;
                }
                // Soft HTML replace without destroy leaves DataTables registered → tn/3 on next init.
                destroyDataTablesIn($root);
                // Detached inputs leave orphan flatpickr calendars on <body>; destroy first.
                if (typeof window.pdsDestroyDatepickers === 'function') {
                    window.pdsDestroyDatepickers($root[0]);
                }
                // Soft replace strips scripts but leaves Rider Done heartbeat intervals alive.
                try {
                    if (window.__pdsRiderDonePresence && typeof window.__pdsRiderDonePresence.release === 'function') {
                        window.__pdsRiderDonePresence.release();
                    }
                } catch (e) {}
                window.__pdsRiderDonePresence = null;
                window.__pdsRiderDoneItemsLock = false;
                // Keep Assign 100 / list filter text across soft HTML swaps.
                var preserveIds = [
                    'assign_100_os_name',
                    'assign_100_customer_search',
                    'assigned_username',
                    'assigned_phone',
                    'os_list_from_date',
                    'os_list_to_date',
                    'mt_from_date',
                    'mt_to_date',
                    'daily_check_from_date',
                    'daily_check_to_date',
                    'rider_items_from_date',
                    'rider_items_to_date',
                    'rr_date',
                    'summary_from',
                    'summary_to',
                    'expenses_from',
                    'expenses_to',
                    'from_date',
                    'to_date'
                ];
                var preserved = {};
                preserveIds.forEach(function (id) {
                    var el = document.getElementById(id);
                    if (el && 'value' in el) preserved[id] = el.value;
                });
                var imported = document.importNode(newRoot, true);
                imported.querySelectorAll('script').forEach(function (s) { s.remove(); });
                $root[0].replaceWith(imported);
                Object.keys(preserved).forEach(function (id) {
                    var el = document.getElementById(id);
                    if (el && 'value' in el) el.value = preserved[id];
                });
                restoreScrollPositions(scrollState);
                $(document).trigger('admin-live:replaced');
                // Re-apply after listeners (table freeze / animations) finish mutating layout.
                restoreScrollPositions(scrollState);
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
        }).always(function () {
            window.__adminLiveSoftReplacing = false;
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
            var clearReloadFlag = function () {
                window.__adminLiveReloading = false;
            };
            try {
                // Prevent DataTables alert() spam when a live reload hits a bad response.
                if (window.jQuery && jQuery.fn.dataTable) {
                    jQuery.fn.dataTable.ext.errMode = function (settings, helpPage, message) {
                        if (window.console && console.warn) {
                            console.warn('DataTables live reload:', message);
                        }
                        clearReloadFlag();
                    };
                }
                table.one('xhr.dt', clearReloadFlag);
                table.ajax.reload(clearReloadFlag, false);
            } catch (e) {
                clearReloadFlag();
            }
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
