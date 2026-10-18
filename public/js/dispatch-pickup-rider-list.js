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

    function matchNameOrPhone(term, name, phone) {
        if (typeof window.pdsMatchNameOrPhone === 'function') {
            return window.pdsMatchNameOrPhone(term, name, phone);
        }
        var query = String(term || '').toLowerCase().trim();
        if (!query) {
            return true;
        }
        if (String(name || '').toLowerCase().indexOf(query) !== -1) {
            return true;
        }
        var rowPhone = String(phone || name || '');
        if (rowPhone.toLowerCase().indexOf(query) !== -1) {
            return true;
        }
        var qDigits = String(query).replace(/\D+/g, '');
        while (qDigits.indexOf('95') === 0 && qDigits.length >= 10) {
            qDigits = qDigits.slice(2);
        }
        if (qDigits.charAt(0) === '0') {
            qDigits = qDigits.replace(/^0+/, '');
        }
        if (qDigits.length < 3) {
            return false;
        }
        var rowDigits = String(rowPhone).replace(/\D+/g, '');
        while (rowDigits.indexOf('95') === 0 && rowDigits.length >= 10) {
            rowDigits = rowDigits.slice(2);
        }
        if (rowDigits.charAt(0) === '0') {
            rowDigits = rowDigits.replace(/^0+/, '');
        }
        return rowDigits.indexOf(qDigits) !== -1;
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
            minimumResultsForSearch: 0,
            matcher: function (params, data) {
                if ($.trim(params.term || '') === '') {
                    return data;
                }
                if (!data.id && data.id !== 0) {
                    return null;
                }
                var phone = '';
                if (data.element) {
                    phone = $(data.element).attr('data-phone')
                        || $(data.element).data('phone')
                        || '';
                }
                return matchNameOrPhone(params.term, data.text || '', phone || data.text || '')
                    ? data
                    : null;
            }
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

    function realignDispatchOrderTable() {
        var table = getTableApi();
        if (!table) {
            return;
        }
        try {
            table.columns.adjust();
        } catch (e) {}
        try {
            var wrap = $(table.table().container());
            var $bodyTable = wrap.find('.dataTables_scrollBody table');
            var $headInner = wrap.find('.dataTables_scrollHeadInner');
            var $footInner = wrap.find('.dataTables_scrollFootInner');
            if ($bodyTable.length && $headInner.length) {
                var w = Math.ceil(($bodyTable[0] && $bodyTable[0].scrollWidth) || $bodyTable.outerWidth());
                $bodyTable.css({ width: w + 'px', minWidth: w + 'px' });
                $headInner.width(w);
                $headInner.children('table').css({ width: w + 'px', minWidth: w + 'px' });
                if ($footInner.length) {
                    $footInner.width(w);
                    $footInner.children('table').css({ width: w + 'px', minWidth: w + 'px' });
                }
            }
        } catch (e) {}
        if (typeof window.pdsSyncDataTableFreeze === 'function') {
            try {
                window.pdsSyncDataTableFreeze(table.table().node());
            } catch (e) {}
        }
    }

    function initAllPickupRiderSelects(config) {
        $('.dispatch-pickup-rider-select2').each(function () {
            initPickupRiderSelect($(this), config);
        });
        window.setTimeout(realignDispatchOrderTable, 60);
        window.setTimeout(realignDispatchOrderTable, 220);
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
