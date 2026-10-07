(function (window, $) {
    'use strict';

    if (!window.jQuery) return;

    var CONTENT = '#adminSpaContent';
    var PAGE_SCRIPTS = '#adminSpaPageScripts';
    var navigating = false;
    var loadedScriptSrc = {};
    var visitWatchdog = null;

    function absUrl(href) {
        try {
            return new URL(href, window.location.href).href;
        } catch (e) {
            return null;
        }
    }

    function sameOrigin(url) {
        try {
            return new URL(url).origin === window.location.origin;
        } catch (e) {
            return false;
        }
    }

    function shouldIgnoreLink(el, event) {
        if (!el || el.tagName !== 'A') return true;
        if (event && (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey)) return true;
        if (el.target && el.target !== '' && el.target !== '_self') return true;
        if (el.hasAttribute('download')) return true;
        if (el.getAttribute('data-spa-ignore') != null) return true;
        if (el.classList.contains('loadRemoteModel')) return true;
        if (el.classList.contains('notifyList')) return true;
        if (el.getAttribute('data-toggle')) return true;
        if (el.getAttribute('data-dismiss')) return true;
        // role=tab alone must NOT block SPA — branch/status tabs use role=tab with real hrefs.
        // Only ignore non-navigating UI tabs (# / javascript:).

        var href = el.getAttribute('href') || '';
        if (!href || href.charAt(0) === '#' || href.indexOf('javascript:') === 0) return true;

        var url = absUrl(href);
        if (!url || !sameOrigin(url)) return true;

        var path = '';
        try {
            path = new URL(url).pathname || '';
        } catch (e) {
            return true;
        }

        if (/\/logout(\/|$)/i.test(path)) return true;
        if (/\/change[-_]language/i.test(path) || /\/language\//i.test(path)) return true;
        if (/\.(pdf|zip|csv|xlsx|xls|doc|docx|png|jpe?g|gif|webp)(\?|$)/i.test(path)) return true;

        return false;
    }

    function hideSplash() {
        var splash = document.getElementById('loading');
        if (splash) {
            splash.style.setProperty('display', 'none', 'important');
            splash.style.setProperty('pointer-events', 'none', 'important');
            splash.style.setProperty('visibility', 'hidden', 'important');
            splash.style.setProperty('opacity', '0', 'important');
        }
        var loader = document.getElementById('loader');
        if (loader) {
            loader.style.setProperty('display', 'none', 'important');
            loader.style.setProperty('visibility', 'hidden', 'important');
            loader.style.setProperty('opacity', '0', 'important');
        }
        var load = document.getElementById('load');
        if (load) load.style.display = 'none';
        // After first paint, CSS keeps splash locked so SPA nav never looks like a hard reload.
        if (document.body) document.body.classList.add('pds-splash-done');
    }

    function showLoading(on) {
        // Never reuse #loading splash — it looks like a full browser reload.
        hideSplash();
        var root = document.body;
        var content = document.querySelector(CONTENT);
        if (on) {
            if (root) root.classList.add('pds-spa-navigating');
            if (content) content.classList.add('pds-spa-loading');
        } else {
            if (root) root.classList.remove('pds-spa-navigating');
            if (content) content.classList.remove('pds-spa-loading');
        }
    }

    function isSpaNavLink(el) {
        if (!el || !el.closest) return false;
        // Header dropdowns are portaled to body (#pdsMainNavPortal) — must be in scope.
        return !!el.closest(
            '#mm-sidebar-toggle, .pds-sidebar, .pds-topbar, #adminSpaContent, #pdsMainNavPortal, .pds-main-nav-portal'
        );
    }

    var hardReload = window.location.reload.bind(window.location);

    function pdsAdminGo(url, opts) {
        url = absUrl(url);
        if (!url) return;
        if (window.AdminSpa && typeof window.AdminSpa.visit === 'function') {
            return window.AdminSpa.visit(url, opts || {});
        }
        window.location.assign(url);
    }

    function pdsAdminReload() {
        if (window.__pdsForceHardReload) {
            window.__pdsForceHardReload = false;
            return hardReload();
        }
        // Guard against page scripts that call location.reload() during SPA eval.
        if (navigating || window.__pdsAdminReloading) {
            return $.Deferred().resolve().promise();
        }
        if (window.AdminSpa && typeof window.AdminSpa.reload === 'function') {
            window.__pdsAdminReloading = true;
            return $.when(window.AdminSpa.reload()).always(function () {
                window.__pdsAdminReloading = false;
            });
        }
        return hardReload();
    }

    function rememberExistingScripts() {
        $('script[src]').each(function () {
            var src = this.getAttribute('src');
            if (src) loadedScriptSrc[src] = true;
        });
    }

    function destroyAllDataTables() {
        // Prefer registered Yajra instances first (works even if DOM nodes were swapped).
        if (window.LaravelDataTables && typeof window.LaravelDataTables === 'object') {
            Object.keys(window.LaravelDataTables).forEach(function (key) {
                try {
                    var api = window.LaravelDataTables[key];
                    if (api && typeof api.destroy === 'function') {
                        api.destroy(true);
                    }
                } catch (e) {}
            });
        }
        window.LaravelDataTables = {};

        try {
            if ($.fn.dataTable && typeof $.fn.dataTable.tables === 'function') {
                var all = $.fn.dataTable.tables({ api: true });
                if (all && typeof all.destroy === 'function') {
                    all.destroy(true);
                }
            }
        } catch (e) {}

        try {
            if ($.fn.DataTable) {
                $(CONTENT + ' table').each(function () {
                    if ($.fn.DataTable.isDataTable(this)) {
                        // Never call .clear() — server-side tables throw and skip destroy.
                        $(this).DataTable().destroy(true);
                    }
                });
            }
        } catch (e) {}
    }

    function patchDataTableReinit() {
        if (!$.fn.DataTable || $.fn.DataTable.__pdsSpaPatched) return;
        var original = $.fn.DataTable;
        var patched = function (options) {
            if (options && typeof options === 'object' && this.length) {
                var already = false;
                try {
                    already = $.fn.dataTable && $.fn.dataTable.isDataTable(this[0]);
                } catch (e) {
                    already = false;
                }
                if (already) {
                    options = $.extend({}, options, { destroy: true });
                }
            }
            return original.apply(this, arguments);
        };
        $.extend(patched, original);
        patched.__pdsSpaPatched = true;
        $.fn.DataTable = patched;
        if ($.fn.dataTable) {
            $.fn.DataTable.isDataTable = $.fn.dataTable.isDataTable || original.isDataTable;
        }
    }

    function destroyPageWidgets() {
        destroyAllDataTables();

        if (window.__pdsPageLiveHandle && typeof window.__pdsPageLiveHandle.stop === 'function') {
            try { window.__pdsPageLiveHandle.stop(); } catch (e) {}
        }
        window.__pdsPageLiveHandle = null;
        window.__pdsPageLiveBooted = false;

        try {
            if ($.fn.select2) {
                $(CONTENT + ' .select2-hidden-accessible').select2('destroy');
            }
        } catch (e) {}

        $(CONTENT).find('*').off();
    }

    function collectScriptsFromNode(root) {
        if (!root) return [];
        var list = [];
        root.querySelectorAll('script').forEach(function (node) {
            var type = (node.getAttribute('type') || '').toLowerCase();
            if (type && type !== 'text/javascript' && type !== 'application/javascript') return;
            list.push({
                src: node.getAttribute('src') || '',
                code: (node.text || node.textContent || '').trim()
            });
        });
        return list;
    }

    function setHtmlWithoutScripts($host, sourceEl) {
        if (!sourceEl) {
            $host.empty();
            return;
        }
        var clone = sourceEl.cloneNode(true);
        clone.querySelectorAll('script').forEach(function (s) { s.remove(); });
        // Avoid jQuery .html() auto-evaluating scripts on older jQuery builds.
        $host.empty();
        while (clone.firstChild) {
            $host[0].appendChild(clone.firstChild);
        }
    }

    function runScriptList(scripts) {
        if (!scripts || !scripts.length) return $.Deferred().resolve().promise();

        destroyAllDataTables();

        var chain = $.Deferred().resolve();
        scripts.forEach(function (item) {
            chain = chain.then(function () {
                if (item.src) {
                    if (loadedScriptSrc[item.src] || document.querySelector('script[src="' + item.src.replace(/"/g, '\\"') + '"]')) {
                        loadedScriptSrc[item.src] = true;
                        return;
                    }
                    return $.ajax({
                        url: item.src,
                        dataType: 'script',
                        cache: true
                    }).done(function () {
                        loadedScriptSrc[item.src] = true;
                    }).fail(function () {
                        if (window.console && console.debug) {
                            console.debug('[admin-spa] script load failed', item.src);
                        }
                    });
                }
                if (item.code) {
                    $.globalEval(item.code);
                }
            });
        });
        return chain;
    }

    function normalizePath(pathname) {
        var p = (pathname || '/').replace(/\/+$/, '') || '/';
        if (p === '/index.php') return '/';
        return p;
    }

    function pathMatches(linkPath, currentPath) {
        if (linkPath === currentPath) return true;
        // Avoid treating "/" or very short roots as a prefix match for everything.
        if (linkPath === '/' || linkPath === '/home') {
            return currentPath === '/' || currentPath === '/home';
        }
        return currentPath.indexOf(linkPath + '/') === 0;
    }

    function scoreNavLink(anchor, loc, path, currentStatus, dedicated) {
        var href = anchor.getAttribute('href') || '';
        if (!href || href.charAt(0) === '#' || href.indexOf('javascript:') === 0) return -1;
        if (anchor.getAttribute('data-toggle') || anchor.getAttribute('data-dismiss')) return -1;

        var h;
        try {
            h = new URL(href, window.location.href);
        } catch (e) {
            return -1;
        }
        if (h.origin !== loc.origin) return -1;

        var p = normalizePath(h.pathname);
        if (!pathMatches(p, path)) return -1;

        var hrefStatus = h.searchParams.get('dispatch_status') || '';
        if (dedicated) {
            if (hrefStatus !== currentStatus) return -1;
        } else if (hrefStatus) {
            return -1;
        }

        var score = p.length * 100;
        // Exact path beats prefix matches (e.g. /users wins over /users/create candidate).
        if (p === path) score += 50;
        h.searchParams.forEach(function (value, key) {
            if (loc.searchParams.get(key) === value) score += 10;
        });
        return score;
    }

    function findBestNavAnchor(root, loc, path, currentStatus, dedicated) {
        if (!root) return null;
        var best = null;
        var bestScore = -1;
        var anchors = root.querySelectorAll('a[href]');
        for (var i = 0; i < anchors.length; i++) {
            var score = scoreNavLink(anchors[i], loc, path, currentStatus, dedicated);
            if (score > bestScore) {
                bestScore = score;
                best = anchors[i];
            }
        }
        return best;
    }

    function updateSidebarActive(url) {
        var loc;
        try {
            loc = new URL(url, window.location.href);
        } catch (e) {
            return;
        }
        var path = normalizePath(loc.pathname);
        var currentStatus = loc.searchParams.get('dispatch_status') || '';
        var dedicated = currentStatus !== '' && currentStatus !== 'all';

        // Legacy left sidebar (hidden in header-nav mode)
        var sidebar = document.getElementById('mm-sidebar-toggle');
        if (sidebar) {
            $(sidebar).find('li').removeClass('active');
            var bestSidebar = findBestNavAnchor(sidebar, loc, path, currentStatus, dedicated);
            if (bestSidebar) {
                var $li = $(bestSidebar).closest('li');
                $li.addClass('active');
                $li.parents('li').addClass('active');
                $li.parents('ul.submenu').addClass('show');
                $li.parents('li').children('a[data-toggle="collapse"]').attr('aria-expanded', 'true');
            }
        }

        // Header main nav — this drives the orange underline.
        var headerNav = document.querySelector('.pds-main-nav');
        if (!headerNav) return;

        headerNav.querySelectorAll('.pds-main-nav__item.is-active, .pds-main-nav__link.is-active, .pds-main-nav__sublink.is-active')
            .forEach(function (el) { el.classList.remove('is-active'); });

        // Include portaled More/dropdown links outside .pds-main-nav.
        var portal = document.getElementById('pdsMainNavPortal');
        if (portal) {
            portal.querySelectorAll('.pds-main-nav__sublink.is-active')
                .forEach(function (el) { el.classList.remove('is-active'); });
        }

        var searchRoots = [headerNav];
        if (portal) searchRoots.push(portal);

        var bestHeader = null;
        var bestHeaderScore = -1;
        searchRoots.forEach(function (root) {
            var candidate = findBestNavAnchor(root, loc, path, currentStatus, dedicated);
            if (!candidate) return;
            var score = scoreNavLink(candidate, loc, path, currentStatus, dedicated);
            if (score > bestHeaderScore) {
                bestHeaderScore = score;
                bestHeader = candidate;
            }
        });

        if (!bestHeader) return;

        bestHeader.classList.add('is-active');

        var item = bestHeader.closest('.pds-main-nav__item');
        if (item) {
            item.classList.add('is-active');
            var topLink = item.querySelector(':scope > .pds-main-nav__link');
            if (topLink) topLink.classList.add('is-active');
        }

        // If active link is a sublink (dropdown / More), mark its parent item too.
        if (bestHeader.classList.contains('pds-main-nav__sublink')) {
            var parentItem = bestHeader.closest('.pds-main-nav__item');
            if (!parentItem) {
                // Portaled More group: find original item by matching href in primary list.
                var href = bestHeader.getAttribute('href');
                if (href) {
                    var original = headerNav.querySelector('a.pds-main-nav__sublink[href="' + href.replace(/"/g, '\\"') + '"], a.pds-main-nav__link[href="' + href.replace(/"/g, '\\"') + '"]');
                    if (original) {
                        parentItem = original.closest('.pds-main-nav__item');
                        original.classList.add('is-active');
                    }
                }
            }
            if (parentItem) {
                parentItem.classList.add('is-active');
                var parentLink = parentItem.querySelector(':scope > .pds-main-nav__link');
                if (parentLink) parentLink.classList.add('is-active');
            }

            // Keep More item underlined when the active link lives in More overflow.
            var moreItem = document.getElementById('pdsMainNavMore');
            if (moreItem && bestHeader.closest('#pdsMainNavMore, #pdsMainNavMoreInner, #pdsMainNavPortal')) {
                var inMore = bestHeader.closest('#pdsMainNavMore, #pdsMainNavMoreInner')
                    || (portal && portal.contains(bestHeader) && bestHeader.closest('.pds-main-nav__dropdown'));
                // Only mark More when the source item is overflow-hidden.
                if (parentItem && parentItem.classList.contains('is-overflow-hidden') && moreItem) {
                    moreItem.classList.add('is-active');
                    var moreLink = moreItem.querySelector(':scope > .pds-main-nav__link');
                    if (moreLink) moreLink.classList.add('is-active');
                }
            }
        }
    }

    function syncLiveOptionsFromDom() {
        var $root = $(CONTENT);
        var page = $root.attr('data-page') || ($('#adminLiveRoot').attr('data-live-page') || 'global');
        var path = window.location.pathname || '';
        var isFormPage = /\/(create|edit)(\/|$)/.test(path)
            || $(CONTENT + ' form#userForm, ' + CONTENT + ' form#orderForm, ' + CONTENT + ' .pds-disable-live-reload, ' + CONTENT + ' form.pds-form-no-live-reload').length > 0;
        var includeDashboard = page === 'home';

        if (typeof window.adminLiveUpdateOptions === 'function') {
            var liveData = (typeof window.adminLiveCollectPageData === 'function')
                ? window.adminLiveCollectPageData()
                : {};
            window.adminLiveUpdateOptions({
                page: page || 'global',
                includeDashboard: includeDashboard,
                // Dashboard stats update from JSON; avoid content replace flashes.
                refreshContent: includeDashboard ? false : !isFormPage,
                onReload: includeDashboard ? function () {} : undefined,
                hardReloadFallback: false,
                data: liveData
            });
        }
    }

    function applyDocumentTitle(doc) {
        var title = doc && doc.querySelector('title') ? doc.querySelector('title').textContent : '';
        if (title) document.title = title;
    }

    function extractParts(html) {
        // DOMParser never executes scripts (unlike jQuery parseHTML/append with keepScripts).
        var doc = new DOMParser().parseFromString(html, 'text/html');
        var content = doc.querySelector(CONTENT) || doc.querySelector('.content-page');
        var scripts = doc.querySelector(PAGE_SCRIPTS);
        var hasPassword = !!doc.querySelector('input[name="password"]');
        var hasShell = !!content;
        return {
            doc: doc,
            content: content,
            scripts: scripts,
            looksLikeLogin: hasPassword && !hasShell
        };
    }

    var pendingNav = null;
    var activeVisitXhr = null;

    function cleanupLeftovers() {
        try {
            $('.modal-backdrop').remove();
            $('.select2-dropdown').remove();
            $('.jconfirm').remove();
            $('body').removeClass('modal-open').css({ paddingRight: '', overflow: '' });
        } catch (e) {}
        try {
            if (document.scrollingElement) document.scrollingElement.scrollTop = 0;
            window.scrollTo(0, 0);
        } catch (e) {}
    }

    function finishVisit() {
        navigating = false;
        if (visitWatchdog) {
            clearTimeout(visitWatchdog);
            visitWatchdog = null;
        }
        showLoading(false);
        activeVisitXhr = null;
        $(CONTENT).removeClass('pds-spa-loading');
        document.body.classList.remove('pds-spa-navigating');
        if (pendingNav) {
            var next = pendingNav;
            pendingNav = null;
            visit(next.url, next.options);
        }
    }

    function afterContentSwap(finalUrl) {
        cleanupLeftovers();
        syncLiveOptionsFromDom();
        if (typeof window.pdsAdminAnimationsRefresh === 'function') {
            window.pdsAdminAnimationsRefresh();
        }
        if (typeof window.pdsResyncFrozenTables === 'function') {
            window.pdsResyncFrozenTables();
        }
        $(document).trigger('admin-spa:navigated', [finalUrl]);
        if (typeof window.adminLiveRefreshNow === 'function') {
            window.adminLiveRefreshNow();
        }
    }

    function visit(url, options) {
        options = options || {};
        url = absUrl(url);
        if (!url) return $.Deferred().reject().promise();

        if (navigating) {
            pendingNav = { url: url, options: options };
            return $.Deferred().resolve().promise();
        }

        if (!options.replace && url === window.location.href) {
            return reload();
        }

        navigating = true;
        showLoading(true);
        if (visitWatchdog) clearTimeout(visitWatchdog);
        visitWatchdog = setTimeout(function () {
            if (activeVisitXhr && typeof activeVisitXhr.abort === 'function') {
                try { activeVisitXhr.abort(); } catch (e) {}
            } else {
                finishVisit();
            }
        }, 12000);

        var request = $.ajax({
            url: url,
            method: 'GET',
            dataType: 'html',
            cache: false,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-Admin-Spa': '1'
            }
        });
        activeVisitXhr = request;

        return request.then(function (html, _status, xhr) {
            var finalUrl = url;
            try {
                if (xhr && xhr.responseURL) finalUrl = xhr.responseURL;
            } catch (e) {}

            var parts = extractParts(html);
            if (parts.looksLikeLogin) {
                window.__pdsForceHardReload = true;
                window.location.assign(finalUrl);
                return;
            }
            if (!parts.content) {
                if (window.console && console.warn) {
                    console.warn('[admin-spa] missing content shell', finalUrl);
                }
                window.location.assign(finalUrl);
                return;
            }

            destroyPageWidgets();
            destroyAllDataTables();

            var $contentHost = $(CONTENT);
            if (!$contentHost.length) {
                if (window.console && console.warn) {
                    console.warn('[admin-spa] #adminSpaContent missing in live DOM');
                }
                return;
            }

            var contentScripts = collectScriptsFromNode(parts.content);
            var pageScripts = collectScriptsFromNode(parts.scripts);

            $contentHost.attr({
                'data-page': parts.content.getAttribute('data-page') || '',
                'class': parts.content.getAttribute('class') || $contentHost.attr('class')
            });
            $contentHost.addClass('pds-spa-loading');
            setHtmlWithoutScripts($contentHost, parts.content);

            var $scriptHost = $(PAGE_SCRIPTS);
            if ($scriptHost.length) {
                $scriptHost.empty();
            }

            applyDocumentTitle(parts.doc);
            updateSidebarActive(finalUrl);

            if (options.replace) {
                window.history.replaceState({ adminSpa: true }, '', finalUrl);
            } else {
                window.history.pushState({ adminSpa: true }, '', finalUrl);
            }

            return runScriptList(contentScripts.concat(pageScripts)).always(function () {
                afterContentSwap(finalUrl);
            });
        }).fail(function (_xhr, textStatus) {
            if (textStatus === 'abort') return;
            if (window.console && console.warn) {
                console.warn('[admin-spa] visit failed', textStatus, url);
            }
        }).always(function () {
            finishVisit();
        });
    }

    function reload() {
        return visit(window.location.href, { replace: true });
    }

    function softRefresh() {
        if (typeof window.adminLiveReloadPage === 'function') {
            window.adminLiveReloadPage();
            return;
        }
        reload();
    }

    function onClick(event) {
        var el = event.target.closest ? event.target.closest('a') : null;
        if (!el) return;
        if (!isSpaNavLink(el)) return;
        if (shouldIgnoreLink(el, event)) return;

        event.preventDefault();
        event.stopPropagation();
        hideSplash();
        visit(el.href);
    }

    function onGetFormSubmit(event) {
        var form = event.target;
        if (!form || form.tagName !== 'FORM') return;
        if (!form.closest || !form.closest('#adminSpaContent')) return;
        if (form.getAttribute('data-spa-ignore') != null) return;
        var method = (form.getAttribute('method') || 'get').toLowerCase();
        if (method !== 'get') return;

        event.preventDefault();
        var action = form.getAttribute('action') || window.location.href;
        var params = $(form).serialize();
        var url = absUrl(action);
        if (params) {
            url += (url.indexOf('?') >= 0 ? '&' : '?') + params;
        }
        visit(url);
    }

    rememberExistingScripts();
    patchDataTableReinit();
    hideSplash();

    // Capture phase so SPA wins before other menu handlers / default navigation.
    document.addEventListener('click', onClick, true);
    document.addEventListener('submit', onGetFormSubmit, true);

    window.addEventListener('popstate', function () {
        visit(window.location.href, { replace: true });
    });

    if (!window.history.state || !window.history.state.adminSpa) {
        window.history.replaceState({ adminSpa: true }, '', window.location.href);
    }

    // Note: browsers keep Location#reload as native; call sites must use pdsAdminReload().

    syncLiveOptionsFromDom();
    updateSidebarActive(window.location.href);

    window.AdminSpa = {
        visit: visit,
        reload: reload,
        softRefresh: softRefresh,
        navigating: function () { return navigating; }
    };

    window.pdsAdminGo = pdsAdminGo;
    window.pdsAdminReload = pdsAdminReload;
    window.adminSpaReload = reload;
    window.adminSpaSoftRefresh = softRefresh;
})(window, jQuery);
