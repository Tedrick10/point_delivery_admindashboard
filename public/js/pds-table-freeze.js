/**
 * Point Delivery — freeze table titles + footer totals while body scrolls.
 * - DataTables: rely on scrollY/scrollX, reveal scrollFoot when totals exist
 * - Plain tables: CSS sticky + height-capped scrollport (no DOM split)
 * Covers every content-page table shell, not only a fixed class list.
 */
(function () {
    var SHELL_SELECTORS = [
        '.pds-rider-table-shell',
        '.pds-rider-table-shell--scroll',
        '.pds-dispatch-to-assign-table-shell',
        '.pds-dispatch-items-table-shell',
        '.pds-table-shell',
        '.pds-report-table-shell',
        '.pds-deliveryman-table-shell',
        '.pds-os-list-table-shell',
        '.pds-cash-payout-shell',
        '.pds-os-settlement-shell',
        '.pds-rider-remit-shell',
        '.pds-daily-check-shell',
        '.pds-dispatch-table-shell',
        '.table-responsive'
    ].join(',');

    var SKIP_TABLE_SELECTORS = [
        '.pds-os-slip__table',
        '.pds-daily-check-slip-table',
        '.pds-check-detail-table',
        '.pds-dispatch-items-datatable',
        '.pds-dispatch-items-table-shell table',
        '.dataTables_scrollBody table',
        '.dataTables_scrollHead table',
        '.dataTables_scrollFoot table'
    ].join(',');

    function hasFooterContent(foot) {
        if (!foot) return false;
        var text = (foot.textContent || '').replace(/\s+/g, ' ').trim();
        return text.length > 0;
    }

    function syncDataTableFreeze(source) {
        var roots = [];
        if (source && source.nodeType === 1) {
            var wrap = source.closest
                ? source.closest('.dataTables_wrapper')
                : null;
            if (wrap) roots.push(wrap);
        } else if (source && source.api) {
            try {
                var node = source.api().table().node();
                var w = node && node.closest ? node.closest('.dataTables_wrapper') : null;
                if (w) roots.push(w);
            } catch (e) {}
        }

        if (!roots.length) {
            document.querySelectorAll('.dataTables_wrapper').forEach(function (el) {
                roots.push(el);
            });
        }

        roots.forEach(function (wrap) {
            var host = wrap.querySelector('.table-responsive, .pds-dt-scroll-host, .pds-table-shell');
            if (host) {
                host.classList.add('pds-dt-scroll-host');
            }
            wrap.classList.add('pds-dt-frozen');

            var foot = wrap.querySelector('.dataTables_scrollFoot');
            if (foot) {
                if (hasFooterContent(foot)) {
                    foot.classList.add('is-visible');
                    var inner = foot.querySelector('.dataTables_scrollFootInner');
                    if (inner) inner.classList.add('is-visible');
                } else {
                    foot.classList.remove('is-visible');
                    var innerHide = foot.querySelector('.dataTables_scrollFootInner');
                    if (innerHide) innerHide.classList.remove('is-visible');
                }
            }
        });
    }

    function shouldSkipTable(table) {
        if (!table || !table.matches) return true;
        if (table.closest(SKIP_TABLE_SELECTORS)) return true;
        if (table.matches(SKIP_TABLE_SELECTORS)) return true;
        if (table.closest('.modal, .swal2-container, .dropdown-menu')) return true;
        if (!table.querySelector('thead th, thead td')) return true;
        // Tiny lookup tables — no freeze needed.
        if (table.querySelectorAll('tbody tr').length < 2) return true;
        return false;
    }

    function ensureScrollShell(table) {
        var shell = table.closest(SHELL_SELECTORS);
        if (shell) return shell;

        // Wrap bare content-page tables so sticky has a height-capped scrollport.
        var parent = table.parentElement;
        if (!parent) return null;
        if (parent.classList.contains('pds-auto-freeze-shell')) return parent;

        var wrap = document.createElement('div');
        wrap.className = 'pds-auto-freeze-shell pds-plain-freeze';
        parent.insertBefore(wrap, table);
        wrap.appendChild(table);
        return wrap;
    }

    function markPlainShells() {
        // Pass 1: known shells
        document.querySelectorAll(SHELL_SELECTORS).forEach(function (shell) {
            // Items / Rider List use page scroll — never height-cap them
            // (capped shells ate the search toolbar and left a huge white gap).
            if (shell.classList.contains('pds-dispatch-items-table-shell') ||
                shell.closest('.pds-dispatch-items-page') ||
                shell.classList.contains('pds-plain-freeze-skip') ||
                shell.closest('.pds-dispatch-rider-list-page')) {
                shell.classList.add('pds-plain-freeze-skip');
                shell.classList.remove('pds-plain-freeze');
                return;
            }
            if (shell.querySelector('.dataTables_wrapper, .dataTables_scroll, .pds-frozen-table__head')) {
                shell.classList.add('pds-plain-freeze-skip');
                shell.classList.remove('pds-plain-freeze');
                return;
            }
            var table = shell.querySelector('table');
            if (!table || shouldSkipTable(table)) return;
            shell.classList.add('pds-plain-freeze');
            shell.classList.remove('pds-no-freeze');
            shell.querySelectorAll('table').forEach(function (t) {
                if (!shouldSkipTable(t)) t.classList.add('pds-sticky-freeze');
            });
        });

        // Pass 2: any remaining content-page tables (covers missed screens)
        document.querySelectorAll('.content-page table, #adminSpaContent table, #adminLiveRoot table').forEach(function (table) {
            if (shouldSkipTable(table)) return;
            if (table.classList.contains('pds-sticky-freeze')) return;
            if (table.closest('.dataTables_wrapper, .pds-frozen-table__head, .pds-plain-freeze-skip')) return;

            var shell = ensureScrollShell(table);
            if (!shell) return;
            if (shell.querySelector('.dataTables_wrapper, .dataTables_scroll')) {
                shell.classList.add('pds-plain-freeze-skip');
                return;
            }
            shell.classList.add('pds-plain-freeze');
            shell.classList.remove('pds-no-freeze');
            table.classList.add('pds-sticky-freeze');
        });
    }

    window.pdsSyncDataTableFreeze = syncDataTableFreeze;

    function boot() {
        syncDataTableFreeze();
        markPlainShells();
        if (window.pdsResyncFrozenTables) {
            try { window.pdsResyncFrozenTables(); } catch (e) {}
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
    window.addEventListener('load', boot);
    document.addEventListener('admin-spa:navigated', boot);
    document.addEventListener('admin-live:replaced', boot);
    $(document).on('draw.dt', function (e, settings) {
        try {
            syncDataTableFreeze(settings && settings.nTable ? settings.nTable : null);
        } catch (err) {}
    });
})();
