(function (window, $) {
    'use strict';

    function resolveDataTable(selector) {
        if (window.LaravelDataTables && window.LaravelDataTables['dataTableBuilder']) {
            return window.LaravelDataTables['dataTableBuilder'];
        }
        var $table = $(selector || '.pds-dispatch-datatable, .pds-dispatch-items-datatable, .dataTable').first();
        if ($table.length && $.fn.DataTable && $.fn.DataTable.isDataTable($table)) {
            return $table.DataTable();
        }
        return null;
    }

    window.bootAdminOrderListLiveRefresh = function (options) {
        options = options || {};
        var url = options.url;
        var intervalMs = options.intervalMs || 5000;
        var tableSelector = options.tableSelector || '';
        if (!url) return;

        var lastVersion = null;
        var inFlight = false;
        var reloading = false;
        var timer = null;

        function reloadTable() {
            if (typeof options.onReload === 'function') {
                options.onReload();
                return;
            }
            if (typeof window.reloadDispatchItemsTable === 'function' && options.useItemsReload) {
                window.reloadDispatchItemsTable();
                return;
            }
            var table = resolveDataTable(tableSelector);
            if (!table || typeof table.ajax !== 'object' || typeof table.ajax.reload !== 'function') {
                return;
            }
            if (reloading) return;
            reloading = true;
            table.ajax.reload(function () {
                reloading = false;
            }, false);
        }

        function poll() {
            if (document.hidden || inFlight) return;
            inFlight = true;
            $.ajax({
                url: url,
                method: 'GET',
                dataType: 'json',
                cache: false
            }).done(function (res) {
                var version = res && res.version ? String(res.version) : null;
                if (!version) return;
                if (lastVersion === null) {
                    lastVersion = version;
                    return;
                }
                if (version !== lastVersion) {
                    lastVersion = version;
                    reloadTable();
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
            if (document.hidden) {
                stop();
            } else {
                start();
            }
        });

        start();
    };
})(window, jQuery);
