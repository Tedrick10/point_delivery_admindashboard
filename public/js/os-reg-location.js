(function (window, $) {
    'use strict';

    var SEARCH_LANG = {
        noResults: function () { return 'မတွေ့ပါ'; },
        searching: function () { return 'ရှာနေသည်...'; },
        inputTooShort: function () { return 'ရှာရန် ရိုက်ပါ'; }
    };

    function townLabel(town) {
        var mm = town && town.name_mm ? String(town.name_mm) : '';
        var en = town && town.name_en ? String(town.name_en) : '';
        if (mm && en) {
            return mm + ' (' + en + ')';
        }
        return mm || en;
    }

    function parseTownshipsAttr(raw) {
        if (!raw) {
            return [];
        }
        if (Array.isArray(raw)) {
            return raw;
        }
        try {
            var parsed = typeof raw === 'string' ? JSON.parse(raw) : raw;
            return Array.isArray(parsed) ? parsed : [];
        } catch (err) {
            return [];
        }
    }

    function readStatesFromDom($root) {
        var el = ($root && $root.length ? $root.find('#reg_mm_location_data') : $('#reg_mm_location_data')).get(0);
        if (!el) {
            return [];
        }
        try {
            var raw = el.getAttribute('data-states')
                || el.textContent
                || el.innerText
                || '';
            var parsed = JSON.parse(raw);
            return Array.isArray(parsed) ? parsed : [];
        } catch (err) {
            return [];
        }
    }

    function townsForSelectedState($state, states) {
        var $selected = $state.find('option:selected');
        var fromAttr = parseTownshipsAttr($selected.attr('data-townships'));
        if (fromAttr.length) {
            return fromAttr;
        }

        fromAttr = parseTownshipsAttr($selected.data('townships'));
        if (fromAttr.length) {
            return fromAttr;
        }

        var value = String($state.val() || '').trim();
        if (!value || !states.length) {
            return [];
        }

        var state = states.find(function (row) {
            return String(row.name_mm || '') === value
                || String(row.name_en || '') === value;
        });

        return (state && Array.isArray(state.townships)) ? state.townships : [];
    }

    function destroySelect2($el) {
        if (!$el.length || !$.fn.select2) {
            return;
        }
        if ($el.hasClass('select2-hidden-accessible') || $el.data('select2')) {
            try {
                $el.select2('destroy');
            } catch (err) {}
        }
        $el.next('.select2-container').remove();
    }

    function resolveDropdownParent($el, preferred) {
        if (preferred && preferred.length) {
            return preferred;
        }
        var $modal = $el.closest('.modal-content, .pds-os-account-modal, #remoteModelData, .modal');
        if ($modal.length) {
            return $modal;
        }
        return $(document.body);
    }

    function bindLocationSelect2($el, dropdownParent) {
        if (!$el.length) {
            return false;
        }
        if (!$.fn || !$.fn.select2) {
            return false;
        }

        destroySelect2($el);

        var parent = resolveDropdownParent($el, dropdownParent);
        var config = {
            width: '100%',
            placeholder: $el.attr('data-placeholder') || '',
            allowClear: false,
            dropdownParent: parent,
            minimumResultsForSearch: 0,
            language: SEARCH_LANG,
            matcher: function (params, data) {
                if ($.trim(params.term || '') === '') {
                    return data;
                }
                if (typeof data.text === 'undefined') {
                    return null;
                }
                var term = params.term.toLowerCase();
                var text = String(data.text || '').toLowerCase();
                if (text.indexOf(term) > -1) {
                    return data;
                }
                return null;
            }
        };

        try {
            config.dropdownCssClass = 'pds-os-location-select2-dropdown';
            $el.select2(config);
        } catch (err) {
            try {
                delete config.dropdownCssClass;
                $el.select2(config);
            } catch (err2) {
                return false;
            }
        }

        $el.off('select2:open.osRegSearch select2:close.osRegSearch')
            .on('select2:open.osRegSearch', function () {
                $('body').addClass('select2-location-open');
                window.setTimeout(function () {
                    var $dropdown = $('.select2-container--open .select2-dropdown');
                    $dropdown.addClass('pds-os-location-select2-dropdown');
                    var $search = $dropdown.find('.select2-search__field');
                    if ($search.length) {
                        $search.attr('placeholder', 'ရှာရန် ရိုက်ပါ...').trigger('focus');
                    }
                }, 0);
            })
            .on('select2:close.osRegSearch', function () {
                $('body').removeClass('select2-location-open');
                $('.select2-dropdown').removeClass('pds-os-location-select2-dropdown');
            });

        return $el.hasClass('select2-hidden-accessible');
    }

    function rebuildTownships($box, $state, $township, states, selectedTown, dropdownParent) {
        var hasState = !!String($state.val() || '').trim();
        var towns = hasState ? townsForSelectedState($state, states) : [];
        var placeholderTownship = $box.attr('data-placeholder-township') || '';
        var placeholderLocked = $box.attr('data-placeholder-township-locked') || placeholderTownship;
        var current = selectedTown != null ? String(selectedTown) : String($township.val() || '');
        var matched = false;

        destroySelect2($township);

        $township.empty().append(
            $('<option>', {
                value: '',
                text: hasState ? placeholderTownship : placeholderLocked
            })
        );

        towns.forEach(function (town) {
            var value = town.name_mm || town.name_en || '';
            if (!value) {
                return;
            }
            var isSelected = !!(current && (
                current === value
                || current === town.name_en
                || current === town.name_mm
            ));
            if (isSelected) {
                matched = true;
            }
            $township.append($('<option>', {
                value: value,
                text: townLabel(town),
                selected: isSelected
            }));
        });

        if (current && !matched) {
            $township.append($('<option>', {
                value: current,
                text: current,
                selected: true
            }));
            matched = true;
        }

        if (hasState) {
            $township.prop('disabled', false).removeAttr('disabled');
        } else {
            $township.prop('disabled', true);
        }

        $township.val(matched ? current : '');
        bindLocationSelect2($township, dropdownParent);
    }

    window.initOsRegLocationFields = function ($form, options) {
        options = options || {};
        if (!$form || !$form.length) {
            $form = $('#user_form');
        }
        if (!$form.length) {
            $form = $('#os_account_form');
        }
        if (!$form.length) {
            $form = $('#reg_location_box').closest('form');
        }
        if (!$form.length) {
            return;
        }

        var $box = $form.find('#reg_location_box');
        var $state = $form.find('#reg_state');
        var $township = $form.find('#reg_township');
        if (!$box.length || !$state.length || !$township.length) {
            return;
        }

        $box.data('osLocationBound', 1);

        var states = readStatesFromDom($form);
        if (!states.length && window.PDS_MYANMAR_NRC_DATA && Array.isArray(window.PDS_MYANMAR_NRC_DATA.states)) {
            states = window.PDS_MYANMAR_NRC_DATA.states;
        }

        var dropdownParent = options.dropdownParent
            ? $(options.dropdownParent)
            : resolveDropdownParent($state, null);

        bindLocationSelect2($state, dropdownParent);

        $state.off('change.osRegLocation select2:select.osRegLocation')
            .on('change.osRegLocation select2:select.osRegLocation', function () {
                rebuildTownships($box, $state, $township, states, '', dropdownParent);
            });

        var initialTown = $box.attr('data-selected-township') || $township.val() || '';
        rebuildTownships($box, $state, $township, states, initialTown, dropdownParent);
    };

    function bootVisibleLocationFields() {
        if (!$ || !$.fn) {
            return;
        }
        var $box = $('#reg_location_box');
        if (!$box.length) {
            return;
        }
        var $form = $box.closest('form');
        window.initOsRegLocationFields($form, {
            force: true,
            dropdownParent: resolveDropdownParent($box.find('#reg_state'), null)
        });
    }

    function bootWhenSelect2Ready(attempt) {
        attempt = attempt || 0;
        if ($.fn && $.fn.select2) {
            bootVisibleLocationFields();
            return;
        }
        if (attempt > 40) {
            return;
        }
        window.setTimeout(function () {
            bootWhenSelect2Ready(attempt + 1);
        }, 50);
    }

    $(function () {
        bootWhenSelect2Ready(0);
    });

    $(document).on('admin-spa:navigated', function () {
        window.setTimeout(function () { bootWhenSelect2Ready(0); }, 0);
        window.setTimeout(function () { bootWhenSelect2Ready(0); }, 150);
        window.setTimeout(function () { bootWhenSelect2Ready(0); }, 400);
    });

    $(document).on('shown.bs.modal', '#remoteModelData', function () {
        window.setTimeout(function () { bootWhenSelect2Ready(0); }, 50);
        window.setTimeout(function () { bootWhenSelect2Ready(0); }, 200);
    });
})(window, window.jQuery);
