(function ($) {
    'use strict';

    function readDataAttr($el, name) {
        if (!$el || !$el.length) {
            return '';
        }

        var attrValue = $el.attr('data-' + name);
        if (attrValue !== undefined && attrValue !== null && attrValue !== '') {
            return String(attrValue);
        }

        var camelKey = name.replace(/-([a-z])/g, function (_, char) {
            return char.toUpperCase();
        });
        var dataValue = $el.data(camelKey);
        if (dataValue !== undefined && dataValue !== null && dataValue !== '') {
            return String(dataValue);
        }

        return '';
    }

    function getOrderId($select) {
        return readDataAttr($select, 'order-id')
            || readDataAttr($select.closest('.pds-dispatch-pickup-rider-cell'), 'order-id');
    }

    function getTableApi() {
        return window.LaravelDataTables && window.LaravelDataTables['dataTableBuilder'];
    }

    function syncPickupRiderCellState($select) {
        var $cell = $select.closest('.pds-dispatch-pickup-rider-cell');
        var hasRider = !!String($select.val() || '').trim();
        $cell.toggleClass('is-assigned', hasRider);
        $cell.toggleClass('is-unassigned', !hasRider);
    }

    function destroyPickupRiderSelect($select) {
        if (!$select.length || !$select.hasClass('select2-hidden-accessible')) {
            return;
        }

        $select.off('.pickupRider');
        try {
            $select.select2('destroy');
        } catch (e) {
            // Ignore stale Select2 instances after DataTable redraw.
        }
    }

    function initPickupRiderSelect($select, config) {
        if (!$select.length) {
            return;
        }

        destroyPickupRiderSelect($select);

        var currentId = readDataAttr($select, 'current') || String($select.val() || '');
        var placeholder = readDataAttr($select, 'placeholder') || config.placeholder;

        $select.select2({
            width: '100%',
            placeholder: placeholder,
            allowClear: false,
            dropdownParent: $(document.body),
            minimumResultsForSearch: 0
        });

        $select.on('select2:open.pickupRider', function () {
            $('.select2-dropdown').last().addClass('pds-dispatch-pickup-rider-dropdown');
        });

        $select.on('select2:close.pickupRider', function () {
            $('.select2-dropdown.pds-dispatch-pickup-rider-dropdown').removeClass('pds-dispatch-pickup-rider-dropdown');
        });

        if (currentId) {
            $select.val(currentId).trigger('change.select2');
        }

        $select.on('select2:select.pickupRider', function (event) {
            var riderId = String((event.params && event.params.data && event.params.data.id) || $select.val() || '');
            syncPickupRiderCellState($select);
            assignPickupRider($select, riderId, config);
        });

        $select.on('change.pickupRider', function () {
            if (!$select.hasClass('select2-hidden-accessible')) {
                assignPickupRider($select, String($select.val() || ''), config);
            }
        });

        syncPickupRiderCellState($select);
    }

    function initAllPickupRiderSelects(config) {
        $('.dispatch-pickup-rider-select2').each(function () {
            initPickupRiderSelect($(this), config);
        });
    }

    function assignPickupRider($select, riderId, config) {
        var orderId = getOrderId($select);
        var previous = readDataAttr($select, 'current');
        var next = String(riderId || '');

        if (!orderId || !next || next === previous) {
            return;
        }

        var url = String(config.assignRouteTemplate).replace('ORDER_ID', orderId);
        var table = getTableApi();

        $.ajax({
            url: url,
            type: 'POST',
            dataType: 'json',
            data: {
                _token: config.csrfToken,
                delivery_man_id: next
            },
            success: function (res) {
                $select.attr('data-current', next);
                if (res && res.rider_name) {
                    $select.attr('data-current-name', res.rider_name);
                }
                syncPickupRiderCellState($select);

                if (typeof showMessage === 'function' && res && res.message) {
                    showMessage(res.message);
                }

                if (table) {
                    table.ajax.reload(null, false);
                }
            },
            error: function (xhr) {
                $select.val(previous || null).trigger('change.select2');
                $select.attr('data-current', previous || '');
                syncPickupRiderCellState($select);

                var msg = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : config.errorMessage;

                if (typeof errorMessage === 'function') {
                    errorMessage(msg);
                } else if (typeof showMessage === 'function') {
                    showMessage(msg);
                }
            }
        });
    }

    window.initDispatchPickupRiderList = function (config) {
        var table = getTableApi();

        function scheduleInit() {
            window.setTimeout(function () {
                initAllPickupRiderSelects(config);
            }, 50);
        }

        if (table) {
            table.off('draw.dt.initPickupRider init.dt.initPickupRider');
            table.on('draw.dt.initPickupRider', scheduleInit);
            table.on('init.dt.initPickupRider', scheduleInit);
        }

        $('#dataTableBuilder').off('draw.dt.initPickupRider init.dt.initPickupRider');
        $('#dataTableBuilder').on('draw.dt.initPickupRider init.dt.initPickupRider', scheduleInit);

        scheduleInit();
    };

    window.bootDispatchPickupRiderList = function (config, attempt) {
        attempt = attempt || 0;

        if (!window.jQuery || !$.fn.select2) {
            if (attempt < 40) {
                window.setTimeout(function () {
                    window.bootDispatchPickupRiderList(config, attempt + 1);
                }, 100);
            }
            return;
        }

        if (!getTableApi()) {
            if (attempt < 40) {
                window.setTimeout(function () {
                    window.bootDispatchPickupRiderList(config, attempt + 1);
                }, 100);
            }
            return;
        }

        window.initDispatchPickupRiderList(config);
    };
})(jQuery);
