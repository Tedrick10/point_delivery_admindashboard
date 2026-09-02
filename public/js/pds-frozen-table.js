(function () {
    function ensureColgroup(table, count) {
        var cg = table.querySelector('colgroup');
        if (!cg) {
            cg = document.createElement('colgroup');
            table.insertBefore(cg, table.firstChild);
        }
        while (cg.children.length < count) {
            cg.appendChild(document.createElement('col'));
        }
        while (cg.children.length > count) {
            cg.removeChild(cg.lastChild);
        }
        return cg;
    }

    function cellIsSpanned(cell) {
        return !cell || (cell.colSpan || 1) > 1 || (cell.rowSpan || 1) > 1;
    }

    function firstDataRow(table) {
        var rows = table.tBodies[0] ? table.tBodies[0].rows : [];
        for (var r = 0; r < rows.length; r++) {
            var cells = rows[r].cells;
            if (!cells.length) continue;
            var spanned = false;
            for (var c = 0; c < cells.length; c++) {
                if (cellIsSpanned(cells[c])) {
                    spanned = true;
                    break;
                }
            }
            if (!spanned && cells.length > 1) return rows[r];
        }
        return null;
    }

    function isNoColumn(cell) {
        if (!cell || !cell.className) return false;
        return /(^|\s)(pds-rider-col-no|pds-money-transfer-no)(\s|$)/.test(cell.className);
    }

    function syncWidths(headTable, bodyTable) {
        var headRow = headTable.tHead && headTable.tHead.rows[0];
        if (!headRow) return;
        var bodyRow = firstDataRow(bodyTable);
        var n = headRow.cells.length;
        var headCg = ensureColgroup(headTable, n);
        var bodyCg = ensureColgroup(bodyTable, n);

        headTable.style.tableLayout = 'auto';
        bodyTable.style.tableLayout = 'auto';
        headTable.style.width = 'max-content';
        bodyTable.style.width = 'max-content';
        headTable.style.minWidth = '0';
        bodyTable.style.minWidth = '0';

        var widths = [];
        var total = 0;
        for (var i = 0; i < n; i++) {
            var headCell = headRow.cells[i];
            var bodyCell = bodyRow && bodyRow.cells[i];
            var hw = headCell ? Math.ceil(headCell.scrollWidth) + 28 : 52;
            var bw = 0;
            if (bodyCell && !cellIsSpanned(bodyCell)) {
                bw = Math.ceil(bodyCell.scrollWidth) + 28;
            }
            var w = Math.max(hw, bw, 52);
            if (isNoColumn(headCell) || isNoColumn(bodyCell)) {
                w = 56;
            }
            widths.push(w);
            total += w;
        }

        headTable.style.width = total + 'px';
        bodyTable.style.width = total + 'px';
        headTable.style.tableLayout = 'fixed';
        bodyTable.style.tableLayout = 'fixed';
        for (var j = 0; j < n; j++) {
            headCg.children[j].style.width = widths[j] + 'px';
            bodyCg.children[j].style.width = widths[j] + 'px';
        }
    }

    function freezeShell(shell) {
        if (!shell || shell.dataset.pdsFrozen === '1') return;
        if (shell.classList.contains('pds-frozen-table') && shell.querySelector('.pds-frozen-table__head')) {
            shell.dataset.pdsFrozen = '1';
            return;
        }
        if (
            shell.classList.contains('pds-no-freeze')
            || shell.classList.contains('pds-os-settlement-shell')
            || shell.closest('.pds-dispatch-rider-list-page')
            || shell.closest('.dataTables_wrapper')
            || shell.closest('.pds-rider-remit-shell')
        ) {
            return;
        }

        var table = null;
        var kids = shell.children;
        for (var i = 0; i < kids.length; i++) {
            if (kids[i].tagName === 'TABLE') {
                table = kids[i];
                break;
            }
        }
        if (!table) table = shell.querySelector('table');
        if (!table || !table.tHead || table.closest('.pds-frozen-table')) return;
        if (!firstDataRow(table)) return;
        if (table.querySelector('.pds-os-receive-date-group')) return;

        shell.dataset.pdsFrozen = '1';
        shell.classList.add('pds-frozen-table');

        var headWrap = document.createElement('div');
        headWrap.className = 'pds-frozen-table__head';
        var headTable = document.createElement('table');
        headTable.className = table.className;
        headTable.appendChild(table.tHead.cloneNode(true));
        headWrap.appendChild(headTable);

        var bodyWrap = document.createElement('div');
        bodyWrap.className = 'pds-frozen-table__body';

        table.parentNode.insertBefore(headWrap, table);
        table.parentNode.insertBefore(bodyWrap, table);
        bodyWrap.appendChild(table);

        function runSync() {
            syncWidths(headTable, table);
            headWrap.scrollLeft = bodyWrap.scrollLeft;
        }

        shell._pdsFrozenSync = runSync;
        bodyWrap.addEventListener('scroll', function () {
            headWrap.scrollLeft = bodyWrap.scrollLeft;
        });
        window.addEventListener('resize', runSync);
        runSync();
        if (window.requestAnimationFrame) {
            window.requestAnimationFrame(runSync);
        }
        setTimeout(runSync, 120);
        setTimeout(runSync, 400);
    }

    function boot() {
        // Do not freeze DataTables Order List shells (.pds-dispatch-table-shell):
        // splitting thead/tbody there misaligns columns under the wrong headers.
        document.querySelectorAll([
            '.pds-rider-table-shell',
            '.pds-dispatch-to-assign-table-shell',
            '.pds-dispatch-items-table-shell'
        ].join(',')).forEach(freezeShell);
    }

    window.pdsResyncFrozenTables = function () {
        document.querySelectorAll('.pds-frozen-table').forEach(function (shell) {
            if (typeof shell._pdsFrozenSync === 'function') {
                shell._pdsFrozenSync();
            }
        });
        boot();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
    window.addEventListener('load', boot);
})();
