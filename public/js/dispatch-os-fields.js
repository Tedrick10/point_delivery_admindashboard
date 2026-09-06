(function (window, $) {
    'use strict';

    var DISPATCH_FORM_SELECTOR = '#dispatch_order_form';
    var skipSelectHandler = false;
    var osClientCache = {};

    function $dispatchForm() {
        return $(DISPATCH_FORM_SELECTOR);
    }

    function normalizePhone(value) {
        if (!value) {
            return '';
        }

        value = String(value).trim();

        if (/^[A-Z]{2}\s+/i.test(value)) {
            value = value.replace(/^[A-Z]{2}\s+/i, '').trim();
        }

        if (/^[A-Z]{2}\+/i.test(value)) {
            value = '+' + value.replace(/^[A-Z]{2}\+/i, '').replace(/^\+/, '');
        } else if (/^[A-Z]{2}\d+$/i.test(value)) {
            value = '+' + value.replace(/^[A-Z]{2}/i, '');
        }

        value = value.replace(/\s+/g, '');
        while (/^\+95\+/.test(value)) {
            value = '+' + value.slice(3).replace(/^\+/, '');
        }

        var digits = value.replace(/\D/g, '');
        if (!digits) {
            return '';
        }

        while (digits.indexOf('95') === 0 && digits.length >= 12) {
            var rest = digits.slice(2);
            if (/^9\d{7,9}$/.test(rest) || (rest.indexOf('95') === 0 && rest.length >= 10)) {
                digits = rest;
                continue;
            }
            break;
        }

        if (/^0\d+/.test(digits)) {
            digits = digits.replace(/^0+/, '');
        }
        if (/^9\d{7,9}$/.test(digits)) {
            return '+95' + digits;
        }
        if (digits.indexOf('95') === 0) {
            return '+' + digits;
        }

        return '+95' + digits;
    }

    function normalizeOsPayload(item) {
        if (!item || item.id === undefined || item.id === null || item.id === '') {
            return null;
        }

        var text = item.text || item.name || '';
        var name = item.name || text.replace(/\s*\([^)]*\)\s*$/, '').trim();

        return {
            id: String(item.id),
            text: text,
            name: name,
            phone: normalizePhone(item.phone || item.contact_number || ''),
            address: (item.address || '').replace(/\r\n/g, '\n').trim()
        };
    }

    function cacheOsClient(item) {
        var payload = normalizeOsPayload(item);
        if (!payload) {
            return null;
        }

        var existing = osClientCache[payload.id] || {};
        osClientCache[payload.id] = {
            id: payload.id,
            text: payload.text || existing.text,
            name: payload.name || existing.name,
            phone: payload.phone || existing.phone,
            address: payload.address || existing.address
        };

        return osClientCache[payload.id];
    }

    function mergeOsResults(serverResults) {
        (serverResults || []).forEach(cacheOsClient);

        return Object.keys(osClientCache)
            .map(function (id) { return osClientCache[id]; })
            .sort(function (a, b) {
                return (a.name || '').localeCompare(b.name || '');
            });
    }

    function filterOsClients(term) {
        var query = (term || '').toLowerCase().trim();
        var list = mergeOsResults([]);

        if (!query) {
            return list;
        }

        return list.filter(function (row) {
            return (row.name || '').toLowerCase().indexOf(query) !== -1
                || (row.text || '').toLowerCase().indexOf(query) !== -1
                || (row.phone || '').toLowerCase().indexOf(query) !== -1;
        });
    }

    window.getOsClientList = function (term) {
        return filterOsClients(term);
    };

    window.refreshOsClientCache = function (callback) {
        if (!window.pdsOsSearchRoute) {
            if (typeof callback === 'function') {
                callback([]);
            }
            return;
        }

        $.get(window.pdsOsSearchRoute, { list_all: 1 })
            .done(function (res) {
                mergeOsResults(res.results || []);
                if (typeof callback === 'function') {
                    callback(mergeOsResults([]));
                }
            })
            .fail(function () {
                if (typeof callback === 'function') {
                    callback(mergeOsResults([]));
                }
            });
    };

    function setOsContactFields(data) {
        var payload = cacheOsClient(data);
        if (!payload) {
            return;
        }

        var $form = $dispatchForm();
        if (!$form.length) {
            return;
        }

        var $name = $form.find('#os_name');
        var $phone = $form.find('#os_phone');
        var $address = $form.find('#os_address');

        if ($name.length && payload.name) {
            $name.val(payload.name);
        }
        if ($phone.length && payload.phone) {
            $phone.val(payload.phone);
        }
        if ($address.length && payload.address) {
            $address.val(payload.address);
        }
    }

    function setClientSelection(payload) {
        var $client = $dispatchForm().find('#client_id');
        if (!$client.length || !payload || !payload.id) {
            return;
        }

        cacheOsClient(payload);
        skipSelectHandler = true;

        if (!$client.find('option[value="' + payload.id + '"]').length) {
            $client.append(new Option(payload.text, payload.id, false, false));
        }

        $client.val(payload.id);

        if ($client.hasClass('select2-hidden-accessible')) {
            $client.trigger('change.select2');
        }

        window.setTimeout(function () {
            skipSelectHandler = false;
        }, 300);
    }

    function fetchOsClientById(userId, callback) {
        if (!userId || !window.pdsOsSearchRoute) {
            callback(null);
            return;
        }

        var cached = osClientCache[String(userId)] || null;

        $.get(window.pdsOsSearchRoute, { id: userId })
            .done(function (res) {
                var match = (res.results || []).find(function (row) {
                    return String(row.id) === String(userId);
                });
                callback(match ? cacheOsClient(match) : cached);
            })
            .fail(function () {
                callback(cached);
            });
    }

    function applyOsSelection(item) {
        var payload = cacheOsClient(item);
        if (!payload) {
            return;
        }

        var fillFields = function (data) {
            var merged = cacheOsClient(data);
            if (!merged) {
                return;
            }
            setClientSelection(merged);
            setOsContactFields(merged);
        };

        if (payload.phone || payload.address) {
            fillFields(payload);
            return;
        }

        fetchOsClientById(payload.id, function (fresh) {
            fillFields(fresh || payload);
        });
    }

    window.fillDispatchOsFields = function (item) {
        applyOsSelection(item);
    };

    window.initDispatchOsFields = function (options) {
        options = options || {};
        window.pdsOsSearchRoute = options.osSearchRoute || window.pdsOsSearchRoute;

        var $client = $dispatchForm().find('#client_id');
        if (!$client.length || !$.fn.select2) {
            return;
        }

        if ($client.hasClass('select2-hidden-accessible')) {
            $client.select2('destroy');
        }

        $client.select2({
            width: '100%',
            placeholder: options.placeholder || '',
            allowClear: true,
            minimumInputLength: 0,
            ajax: {
                url: window.pdsOsSearchRoute,
                dataType: 'json',
                delay: 200,
                data: function (params) {
                    return {
                        q: params.term || '',
                        list_all: params.term ? 0 : 1
                    };
                },
                transport: function (params, success, failure) {
                    var term = (params.data && params.data.q) ? params.data.q : '';

                    if (!term) {
                        window.refreshOsClientCache(function () {
                            success({ results: mergeOsResults([]) });
                        });
                        return;
                    }

                    $.ajax(params).then(success).fail(failure);
                },
                processResults: function (data) {
                    return { results: mergeOsResults(data.results || []) };
                }
            }
        });

        $client.off('select2:select.pdsOs').on('select2:select.pdsOs', function (e) {
            if (skipSelectHandler) {
                return;
            }

            var data = e.params && e.params.data ? e.params.data : null;
            if (!data || !data.id) {
                return;
            }

            applyOsSelection(data);
        });

        window.refreshOsClientCache();
    };
})(window, window.jQuery);
