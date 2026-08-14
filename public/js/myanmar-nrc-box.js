(function (window, $) {
    'use strict';

    var MyanmarNrc = {
        instances: {},
        data: null,

        boxAttr: function ($box, name) {
            var value = $box.attr('data-' + name);
            return value == null ? '' : String(value);
        },

        getData: function () {
            if (this.data && this.data.states && this.data.states.length) {
                return this.data;
            }
            if (window.PDS_MYANMAR_NRC_DATA && window.PDS_MYANMAR_NRC_DATA.states) {
                this.data = window.PDS_MYANMAR_NRC_DATA;
                return this.data;
            }
            return { states: [], types: [] };
        },

        ensureData: function () {
            var self = this;
            var deferred = $.Deferred();
            var data = self.getData();
            if (data.states && data.states.length) {
                deferred.resolve(data);
                return deferred.promise();
            }
            var url = window.PDS_MYANMAR_NRC_DATA_URL;
            if (!url) {
                deferred.resolve(data);
                return deferred.promise();
            }
            $.getJSON(url).done(function (res) {
                self.data = res;
                window.PDS_MYANMAR_NRC_DATA = res;
                deferred.resolve(res);
            }).fail(function () {
                deferred.resolve(data);
            });
            return deferred.promise();
        },

        destroySelect: function ($el) {
            if ($el && $el.length && $el.hasClass('select2-hidden-accessible') && $.fn.select2) {
                try {
                    $el.select2('destroy');
                } catch (e) {
                    /* ignore */
                }
            }
        },

        init: function (id, options) {
            options = options || {};
            var self = this;
            if (!$('#' + id + '_box').length) {
                return;
            }
            this.ensureData().always(function () {
                self.boot(id, options);
            });
        },

        boot: function (id, options) {
            var self = this;
            var $box = $('#' + id + '_box');
            self.data = self.getData();

            var $region = $('#' + id + '_region');
            var $town = $('#' + id + '_town');
            var $type = $('#' + id + '_type');
            var $number = $('#' + id + '_number');
            var $preview = $('#' + id + '_preview');
            var $counter = $('#' + id + '_counter');
            var emptyPreview = $preview.data('empty-text') || '—';
            var dropdownParent = options.dropdownParent || $('body');
            var placeholderState = self.boxAttr($box, 'placeholder-state') || 'နယ်နိမိတ် ရွေးပါ';
            var placeholderTown = self.boxAttr($box, 'placeholder-town') || 'မြို့နယ် ရွေးပါ';
            var placeholderTownLocked = self.boxAttr($box, 'placeholder-town-locked') || 'အရင်ဆုံး နယ်နိမိတ် ရွေးပါ';
            var placeholderType = self.boxAttr($box, 'placeholder-type') || 'အမျိုးအစား ရွေးပါ';
            var dropdownWidths = { state: 400, town: 420, type: 240 };

            function stylizeDropdown(role, $el) {
                if (!$el || !$el.length) {
                    return;
                }
                var inst = $el.data('select2');
                if (!inst || !inst.$dropdown || !inst.$dropdown.length) {
                    return;
                }
                var width = dropdownWidths[role] || 360;
                if (window.innerWidth < 768) {
                    width = Math.max(window.innerWidth - 32, 300);
                }
                var $dropdown = inst.$dropdown;
                $dropdown
                    .addClass('pds-mm-nrc-select2-dropdown pds-mm-nrc-select2-dropdown--' + role)
                    .attr('data-nrc-role', role);

                $dropdown[0].style.setProperty('width', width + 'px', 'important');
                $dropdown[0].style.setProperty('min-width', width + 'px', 'important');
                $dropdown[0].style.setProperty('max-width', (window.innerWidth - 24) + 'px', 'important');
                $dropdown[0].style.setProperty('background', '#ffffff', 'important');
                $dropdown[0].style.setProperty('color', '#0f172a', 'important');
                $dropdown[0].style.setProperty('border', '1px solid #e2e8f0', 'important');
                $dropdown[0].style.setProperty('border-radius', '10px', 'important');
                $dropdown[0].style.setProperty('overflow', 'visible', 'important');
                $dropdown[0].style.setProperty('z-index', '10060', 'important');

                $dropdown.find('.select2-results, .select2-results__options').each(function () {
                    this.style.setProperty('background', '#ffffff', 'important');
                    this.style.setProperty('color', '#0f172a', 'important');
                });

                $dropdown.find('.select2-results__option').each(function () {
                    this.style.setProperty('display', 'flex', 'important');
                    this.style.setProperty('align-items', 'center', 'important');
                    this.style.setProperty('padding', '0.7rem 0.95rem', 'important');
                    this.style.setProperty('min-height', '2.85rem', 'important');
                    this.style.setProperty('height', 'auto', 'important');
                    this.style.setProperty('line-height', '1.6', 'important');
                    this.style.setProperty('font-size', '0.92rem', 'important');
                    this.style.setProperty('white-space', 'nowrap', 'important');
                    this.style.setProperty('overflow', 'visible', 'important');
                    this.style.setProperty('background', '#ffffff', 'important');
                    this.style.setProperty('color', '#0f172a', 'important');
                });

                $dropdown.find('.select2-search--dropdown .select2-search__field').each(function () {
                    this.style.setProperty('background', '#ffffff', 'important');
                    this.style.setProperty('color', '#0f172a', 'important');
                });
            }

            function fixDropdownWidth(role, $el) {
                stylizeDropdown(role, $el);
            }

            function findStateByRegion(regionVal) {
                if (!regionVal) {
                    return null;
                }
                var states = self.getData().states || [];
                for (var i = 0; i < states.length; i++) {
                    if (String(states[i].code_mm) === String(regionVal)) {
                        return states[i];
                    }
                }
                return null;
            }

            function rebuildTownOptions(regionVal, selectedTown) {
                var locked = !regionVal;
                var state = findStateByRegion(regionVal);
                var townships = (state && state.townships) ? state.townships : [];
                var keepVal = selectedTown || '';

                $town.empty();
                $town.append($('<option value=""></option>'));

                if (!locked && townships.length) {
                    townships.forEach(function (town) {
                        var label = (town.code_mm || '') + ' - ' + (town.name_mm || '');
                        var $opt = $('<option></option>')
                            .val(town.code_mm)
                            .attr('data-short', town.code_mm)
                            .text(label);
                        $town.append($opt);
                    });
                }

                $box.toggleClass('is-town-locked', locked);
                return keepVal;
            }

            function shortLabel(text, element) {
                if (element) {
                    var short = $(element).attr('data-short');
                    if (short) {
                        return short;
                    }
                }
                if (!text) {
                    return '';
                }
                return text.indexOf(' - ') > -1 ? text.split(' - ')[0] : text;
            }

            function initSelect($el, role, extra) {
                extra = extra || {};
                if (!$el || !$el.length || !$.fn.select2) {
                    return $el;
                }
                var placeholderText = extra.placeholder || '';
                self.destroySelect($el);
                $el.prop('disabled', false);
                $el.select2($.extend({
                    width: '100%',
                    dropdownParent: dropdownParent,
                    minimumResultsForSearch: 0,
                    allowClear: false,
                    templateResult: function (data) {
                        if (!data.id) {
                            return data.text || placeholderText;
                        }
                        return data.text || '';
                    },
                    templateSelection: function (data) {
                        if (!data.id) {
                            return placeholderText;
                        }
                        return shortLabel(data.text, data.element);
                    },
                    matcher: function (params, data) {
                        if (data.element && $(data.element).prop('disabled')) {
                            return null;
                        }
                        if (!params.term || params.term.trim() === '') {
                            return data;
                        }
                        if (typeof data.text === 'undefined' || data.text == null) {
                            return null;
                        }
                        return data.text.toUpperCase().indexOf(params.term.toUpperCase()) > -1 ? data : null;
                    }
                }, extra));

                $el.off('select2:open.nrcWidth select2:results:all.nrcWidth')
                    .on('select2:open.nrcWidth', function () {
                        window.setTimeout(function () {
                            stylizeDropdown(role, $el);
                        }, 0);
                        window.setTimeout(function () {
                            stylizeDropdown(role, $el);
                        }, 30);
                    })
                    .on('select2:results:all.nrcWidth', function () {
                        stylizeDropdown(role, $el);
                    });

                return $el;
            }

            function updatePreview() {
                var region = $region.val() || '';
                var town = $town.val() || '';
                var type = $type.val() || '';
                var number = ($number.val() || '').replace(/\D/g, '').slice(0, 6);
                if ($number.val() !== number) {
                    $number.val(number);
                }

                var preview = region;
                if (town) preview += '/' + town;
                if (type) preview += '(' + type + ')';
                if (number) preview += number;

                var hasValue = !!(region || town || type || number);
                $preview
                    .text(hasValue ? preview : emptyPreview)
                    .toggleClass('is-empty', !hasValue)
                    .toggleClass('is-filled', hasValue);
                $box.toggleClass('has-value', hasValue);
                $counter.text(number.length);
            }

            function bindTownSelect(regionVal, selectedTown) {
                var locked = !regionVal;
                var keepVal = rebuildTownOptions(regionVal, selectedTown);
                var townOptions = {
                    minimumResultsForSearch: locked ? Infinity : 0,
                    placeholder: locked ? placeholderTownLocked : placeholderTown,
                    language: {
                        noResults: function () { return 'မတွေ့ပါ'; },
                        searching: function () { return 'ရှာနေသည်...'; }
                    }
                };

                self.destroySelect($town);
                initSelect($town, 'town', townOptions);

                $town.off('select2:opening.nrcTown').on('select2:opening.nrcTown', function (e) {
                    if (!$region.val()) {
                        e.preventDefault();
                        $region.select2('open');
                    }
                });

                $town.val(keepVal || null).trigger('change');
            }

            var lastRegionVal = null;

            function onRegionSelected(regionVal) {
                regionVal = regionVal || '';
                if (lastRegionVal === regionVal) {
                    return;
                }
                lastRegionVal = regionVal;
                bindTownSelect(regionVal, '');
                updatePreview();
            }

            self.destroySelect($region);
            self.destroySelect($type);

            initSelect($region, 'state', {
                minimumResultsForSearch: Infinity,
                placeholder: placeholderState
            });
            initSelect($type, 'type', {
                minimumResultsForSearch: Infinity,
                placeholder: placeholderType
            });

            $region.off('.nrc').on('select2:select.nrc change.nrc', function () {
                onRegionSelected($region.val() || '');
            });

            $town.add($type).off('change.nrc').on('change.nrc', updatePreview);
            $number.off('input.nrc').on('input.nrc', updatePreview);

            var initial = options.values || {};
            if (!initial.region) {
                initial = {
                    region: self.boxAttr($box, 'initial-region'),
                    town: self.boxAttr($box, 'initial-town'),
                    type: self.boxAttr($box, 'initial-type'),
                    number: self.boxAttr($box, 'initial-number')
                };
            }

            bindTownSelect('', '');

            if (initial.region) {
                lastRegionVal = initial.region;
                $region.val(initial.region).trigger('change.select2');
                bindTownSelect(initial.region, initial.town || '');
                if (initial.type) {
                    $type.val(initial.type).trigger('change.select2');
                }
            }

            if (initial.number) {
                $number.val(initial.number);
            }

            $box.addClass('is-ready');
            updatePreview();
            self.instances[id] = true;
        },

        autoInitVisibleBoxes: function () {
            var self = this;
            $('.pds-mm-nrc-box').each(function () {
                var $box = $(this);
                var id = self.boxAttr($box, 'nrc-id');
                if (!id) {
                    return;
                }
                if (id === 'os_nrc' && $('#remoteModelData').length) {
                    return;
                }
                if (self.instances[id] && $box.hasClass('is-ready')) {
                    return;
                }
                var $modalParent = $box.closest('.pds-os-account-modal, .modal-content');
                var $dropdownParent = $modalParent.length ? $modalParent : $box.closest('.pds-user-reg-section--nrc, .pds-mm-nrc-box').first();
                if (!$dropdownParent.length) {
                    $dropdownParent = $('body');
                }
                self.init(id, {
                    dropdownParent: $dropdownParent
                });
            });
        },

        reset: function (id) {
            if (this.instances[id]) {
                delete this.instances[id];
            }
            $('#' + id + '_box').removeClass('is-ready');
        }
    };

    window.PdsMyanmarNrc = MyanmarNrc;

    $(function () {
        function bootAll() {
            if (!$.fn.select2) {
                window.setTimeout(bootAll, 120);
                return;
            }
            MyanmarNrc.ensureData().always(function () {
                MyanmarNrc.autoInitVisibleBoxes();
            });
        }
        bootAll();
    });
})(window, window.jQuery);
