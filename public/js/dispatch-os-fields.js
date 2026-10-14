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

        // Two numbers: 09x / 09y
        if (/[\/,|;]/.test(value)) {
            return value.split(/\s*[\/,|;]\s*/).map(normalizePhone).filter(Boolean).filter(function (part, i, arr) {
                return arr.indexOf(part) === i;
            }).join(' / ');
        }

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
            return '0' + digits;
        }
        if (digits.indexOf('95') === 0 && digits.length > 2) {
            var afterCc = digits.slice(2);
            if (/^9\d{7,9}$/.test(afterCc)) {
                return '0' + afterCc;
            }
        }

        return digits.indexOf('0') === 0 ? digits : (digits.indexOf('9') === 0 ? '0' + digits : digits);
    }

    function looksLikePhone(value) {
        var digits = String(value || '').replace(/\D/g, '');
        return digits.length >= 8 && /^[\d+\s().-]+$/.test(String(value || '').trim());
    }

    function normalizeOsPayload(item) {
        if (!item || item.id === undefined || item.id === null || item.id === '') {
            return null;
        }

        var text = item.text || item.name || '';
        var name = String(item.name || text.replace(/\s*\([^)]*\)\s*$/, '').trim() || '').trim();
        var phoneRaw = item.phone || item.contact_number || '';
        var phone = normalizePhone(phoneRaw);

        // Guard against swapped API / Select2 leftovers: name in phone, phone in name.
        if (name && looksLikePhone(name) && phoneRaw && !looksLikePhone(phoneRaw)) {
            phone = normalizePhone(name);
            name = String(phoneRaw).trim();
        } else if ((!name || looksLikePhone(name)) && phoneRaw && !looksLikePhone(phoneRaw)) {
            name = String(phoneRaw).trim();
            phone = normalizePhone(item.name || '');
        }

        return {
            id: String(item.id),
            text: text || name,
            name: name,
            phone: phone,
            address: (item.address || '').replace(/\r\n/g, '\n').trim()
        };
    }

    function cacheOsClient(item, replace) {
        var payload = normalizeOsPayload(item);
        if (!payload) {
            return null;
        }

        var existing = replace ? {} : (osClientCache[payload.id] || {});
        osClientCache[payload.id] = {
            id: payload.id,
            text: payload.text || existing.text || '',
            name: payload.name || existing.name || '',
            phone: payload.phone || existing.phone || '',
            address: payload.address || existing.address || ''
        };

        return osClientCache[payload.id];
    }

    function sortOsClients(list) {
        return (list || []).slice().sort(function (a, b) {
            return (a.name || '').localeCompare(b.name || '');
        });
    }

    /** Full cached list (A→Z). Used for empty-query browse / modal filter. */
    function getCachedOsClients() {
        return sortOsClients(
            Object.keys(osClientCache).map(function (id) {
                return osClientCache[id];
            })
        );
    }

    /**
     * Merge server rows into cache. When serverResults is provided, return ONLY those
     * rows (so Select2 search respects `q`). Empty array = cache-only callers should
     * use getCachedOsClients() instead of expecting a full dump here.
     */
    function mergeOsResults(serverResults) {
        var rows = serverResults || [];
        rows.forEach(cacheOsClient);

        return sortOsClients(
            rows
                .map(function (item) {
                    var cached = cacheOsClient(item);
                    return cached || normalizeOsPayload(item);
                })
                .filter(Boolean)
        );
    }

    function filterOsClients(term) {
        var query = (term || '').toLowerCase().trim();
        var list = getCachedOsClients();

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
                    callback(getCachedOsClients());
                }
            })
            .fail(function () {
                if (typeof callback === 'function') {
                    callback(getCachedOsClients());
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

        // Always target the text inputs (not the Select2 #client_id which is also labeled OS name).
        var $name = $form.find('input#os_name');
        var $phone = $form.find('input#os_phone');
        var $address = $form.find('input#os_address');

        if ($name.length) {
            $name.val(payload.name || '').trigger('input');
        }
        if ($phone.length) {
            $phone.val(payload.phone || '').trigger('input');
        }
        if ($address.length) {
            $address.val(payload.address || '').trigger('input');
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
                // Replace cache entry so a previous bad name/phone mapping cannot stick.
                callback(match ? cacheOsClient(match, true) : cached);
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

        // Always reload by id so Os Name / Os Phone never swap or stay stale
        // when Select2 only keeps { id, text }.
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
                            success({ results: getCachedOsClients() });
                        });
                        return;
                    }

                    $.ajax(params).then(success).fail(failure);
                },
                processResults: function (data, params) {
                    var term = (params && params.term) ? String(params.term).trim() : '';
                    var serverRows = mergeOsResults(data.results || []);

                    // Typed search must show matches only — never the full A→Z cache
                    // (that kept highlighting "Apple Testing User" for every query).
                    if (term) {
                        if (serverRows.length) {
                            return { results: serverRows };
                        }
                        return { results: filterOsClients(term) };
                    }

                    return { results: getCachedOsClients() };
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
