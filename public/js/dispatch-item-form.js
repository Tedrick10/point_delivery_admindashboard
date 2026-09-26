(function (window, $) {
    'use strict';

    function formatNumber(value) {
        var num = parseFloat(value) || 0;
        return num.toLocaleString(undefined, { maximumFractionDigits: 0 });
    }

    function normalizeName(value) {
        return String(value || '').toLowerCase().replace(/[\s\-_]/g, '');
    }

    function namesMatch(a, b) {
        if (!a || !b) {
            return false;
        }
        if (a === b) {
            return true;
        }
        return a.indexOf(b) >= 0 || b.indexOf(a) >= 0;
    }

    function isMandalayName(value) {
        var n = normalizeName(value);
        return n === normalizeName('Mandalay')
            || n === normalizeName('မန္တလေး')
            || n === 'mdy';
    }

    function samePlaceName(a, b) {
        var left = normalizeName(a);
        var right = normalizeName(b);
        if (!left || !right) {
            return false;
        }
        if (left === right) {
            return true;
        }
        return isMandalayName(a) && isMandalayName(b);
    }

    function getCreditMode($form) {
        var $scope = ($form && $form.length) ? $form : $('#dispatch_item_form');
        var $checked = $scope.find('input[name="credit_to"]:checked');
        if (!$checked.length) {
            $checked = $('input[name="credit_to"]:checked');
        }
        return $checked.val() || 'customer';
    }

    /** Backend credit_to is only os|customer. UI "os_paid" maps to os. */
    function getCreditTo($form) {
        var mode = getCreditMode($form);
        return mode === 'os_paid' ? 'os' : mode;
    }

    function isOsPaidMode($form) {
        return getCreditMode($form) === 'os_paid';
    }

    function resolveSelectValue($field, fallbacks) {
        var value = $field && $field.length ? String($field.val() || '').trim() : '';
        if (value) {
            return value;
        }

        var i;
        for (i = 0; i < (fallbacks || []).length; i++) {
            value = String(fallbacks[i] || '').trim();
            if (value) {
                return value;
            }
        }

        if ($field && $field.length && $field.hasClass('select2-hidden-accessible')) {
            value = String($field.next('.select2-container').find('.select2-selection__rendered').first().text() || '').trim();
            if (value && value.toLowerCase().indexOf('select') !== 0) {
                return value;
            }
        }

        return '';
    }

    function ensureSelectValue($field, value) {
        value = String(value || '').trim();
        if (!$field || !$field.length || !value) {
            return value;
        }

        var current = String($field.val() || '').trim();
        if (current === value) {
            return value;
        }

        var $match = null;
        $field.find('option').each(function () {
            var $opt = $(this);
            var optVal = String($opt.val() || '').trim();
            var optText = String($opt.text() || '').trim();
            if (optVal === value || samePlaceName(optVal, value) || samePlaceName(optText, value)) {
                $match = $opt;
                return false;
            }
        });

        if ($match) {
            $field.val($match.val());
        } else {
            $field.append(new Option(value, value, true, true));
        }

        if ($field.hasClass('select2-hidden-accessible')) {
            $field.trigger('change.select2');
        }

        return String($field.val() || value).trim();
    }

    function updateSummary() {
        var $form = $('#dispatch_item_form');
        var itemValue = parseFloat($('#item_value').val()) || 0;
        var deliAmount = parseFloat($('#deli_amount').val()) || 0;
        var osPaid = isOsPaidMode($form) ? (parseFloat($('#os_paid').val()) || 0) : 0;
        var creditTo = getCreditTo($form);
        var custGet;
        var osToPay;

        if (creditTo === 'os') {
            custGet = itemValue;
            // Item Value 0 + Os Paid: Os To Pay is 0.
            if (itemValue <= 0 && osPaid > 0) {
                osToPay = 0;
            } else {
                osToPay = itemValue - (deliAmount - osPaid);
                // Item Value 0 + Os Pay: do not show a negative Os To Pay.
                if (itemValue <= 0 && osToPay < 0) {
                    osToPay = Math.abs(osToPay);
                }
            }
        } else {
            custGet = itemValue + deliAmount;
            osToPay = custGet - deliAmount;
        }

        $('#summary_cust_get span').text(formatNumber(custGet));
        $('#summary_os_to_pay span').text(formatNumber(osToPay));
    }

    function toggleOsPaidField() {
        var $form = $('#dispatch_item_form');
        var $wrap = $('#os_paid_field_wrap');

        if (isOsPaidMode($form)) {
            $wrap.show();
            // Default Os Paid amount to current Deli when empty.
            var osPaid = parseFloat($('#os_paid').val()) || 0;
            if (osPaid <= 0) {
                syncOsPaidWithDeliAmount(true);
            }
        } else {
            $wrap.hide();
            $('#os_paid').val(0);
        }

        updateSummary();
    }

    /**
     * When "Os Paid" credit mode is on, keep OsPaid in sync with Deli Amount.
     */
    function syncOsPaidWithDeliAmount(force) {
        if (!isOsPaidMode($('#dispatch_item_form'))) {
            return;
        }

        var $osPaid = $('#os_paid');
        if (!$osPaid.length) {
            return;
        }

        if (!force) {
            // Still sync whenever Deli changes while Os Paid mode is active.
        }

        var deliAmount = parseFloat($('#deli_amount').val()) || 0;
        var next = Math.max(0, Math.round(deliAmount));
        var current = Math.round(parseFloat($osPaid.val()) || 0);
        if (current === next) {
            return;
        }

        $osPaid.val(next);
    }

    function onDeliAmountChanged() {
        syncOsPaidWithDeliAmount(false);
        syncDispatchItemSizeAvailability();
        updateSummary();
    }

    function adjustDeliAmountForSizeChange(currentAmount, previousSize, newSize) {
        var next = Math.max(1, Math.min(10, parseInt(newSize, 10) || 0));
        if (next <= 1) {
            return null;
        }

        var prevRaw = parseInt(previousSize, 10);
        var prev = (!prevRaw || prevRaw < 1) ? 0 : Math.max(1, Math.min(10, prevRaw));
        var current = parseFloat(currentAmount);
        if (!isFinite(current)) {
            current = 0;
        }

        if (current <= 0 && prev <= 0) {
            return null;
        }

        var effectivePrev = prev > 0 ? prev : 1;
        return Math.max(0, current + ((next - effectivePrev) * 500));
    }

    function canSelectDispatchItemSize() {
        var deliAmount = parseFloat($('#deli_amount').val());
        return isFinite(deliAmount) && deliAmount > 0;
    }

    function syncDispatchItemSizeAvailability() {
        var enabled = canSelectDispatchItemSize();
        $('.pds-dispatch-item-size-pill').each(function () {
            var $pill = $(this);
            $pill
                .toggleClass('is-disabled', !enabled)
                .prop('disabled', !enabled)
                .attr('aria-disabled', enabled ? 'false' : 'true');
        });
    }

    function initDispatchItemSizeSelector() {
        var $hidden = $('#item_weight');
        if (!$hidden.length) {
            return;
        }

        $(document).off('click.dispatchItemSize', '.pds-dispatch-item-size-pill')
            .on('click.dispatchItemSize', '.pds-dispatch-item-size-pill', function () {
                if (!canSelectDispatchItemSize() || $(this).prop('disabled')) {
                    return;
                }

                var value = $(this).data('sizeValue');
                var previousSize = $hidden.val();
                var $deli = $('#deli_amount');
                var nextAmount = adjustDeliAmountForSizeChange($deli.val(), previousSize, value);

                $hidden.val(value);
                $('.pds-dispatch-item-size-pill')
                    .removeClass('is-selected')
                    .attr('aria-pressed', 'false');
                $(this)
                    .addClass('is-selected')
                    .attr('aria-pressed', 'true');

                // Size(2+) only — Size(1) leaves the field for manual entry.
                if ($deli.length && nextAmount !== null) {
                    $deli.val(nextAmount).trigger('input');
                }
            });

        $(document).off('input.dispatchItemSizeGate change.dispatchItemSizeGate', '#deli_amount')
            .on('input.dispatchItemSizeGate change.dispatchItemSizeGate', '#deli_amount', onDeliAmountChanged);

        syncDispatchItemSizeAvailability();
    }

    function isOsCreditDisabled() {
        var $form = $('#dispatch_item_form');
        if (!$form.length) {
            return false;
        }

        return String($form.data('disableOsCredit') || $form.attr('data-disable-os-credit') || '0') === '1';
    }

    function applyOsCreditRestrictions() {
        if (!isOsCreditDisabled()) {
            return;
        }

        var $osRadio = $('input[name="credit_to"][value="os"]');
        var $osPaidRadio = $('input[name="credit_to"][value="os_paid"]');
        var $customerRadio = $('input[name="credit_to"][value="customer"]');

        $osRadio.prop('disabled', true);
        $osRadio.closest('.pds-dispatch-item-credit-pill').addClass('is-disabled');
        $osPaidRadio.prop('disabled', true);
        $osPaidRadio.closest('.pds-dispatch-item-credit-pill').addClass('is-disabled');

        if ($osRadio.is(':checked') || $osPaidRadio.is(':checked')) {
            $customerRadio.prop('checked', true);
        }

        toggleOsPaidField();
    }

    function setTownshipOptionAmount(optionEl, amount) {
        if (amount === undefined || amount === null || amount === '') {
            return;
        }
        var n = parseFloat(amount);
        if (!isFinite(n) || n < 0) {
            return;
        }
        $(optionEl).attr('data-deli-amount', n).data('deliAmount', n);
    }

    function appendTownshipOption($township, label, value, selected, deliAmount) {
        if (!label) {
            return;
        }
        var $existing = $township.find('option').filter(function () {
            return $(this).val() === value;
        });
        if ($existing.length) {
            setTownshipOptionAmount($existing.get(0), deliAmount);
            if (selected) {
                $township.val(value);
            }
            return;
        }
        var option = new Option(label, value, selected, selected);
        setTownshipOptionAmount(option, deliAmount);
        $township.append(option);
    }

    function applyTownshipDeliAmount() {
        var $opt = $('#item_township option:selected');
        if (!$opt.length || !String($opt.val() || '').trim()) {
            return;
        }
        var base = parseFloat($opt.attr('data-deli-amount'));
        if (!isFinite(base) || base < 0) {
            return;
        }
        var size = parseInt($('#item_weight').val(), 10) || 0;
        var amount = size > 1 ? base + ((size - 1) * 500) : base;
        $('#deli_amount').val(Math.round(amount)).trigger('input');
    }

    function townshipMatchesSelection(town, selected) {
        var sel = normalizeName(selected);
        if (!sel) {
            return false;
        }
        return sel === normalizeName(town.name_mm)
            || sel === normalizeName(town.name_en)
            || sel === normalizeName(town.label);
    }

    function csrfHeaders() {
        return {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        };
    }

    function formDispatchOptions() {
        return $('#dispatch_item_form').data('dispatch-item-options') || {};
    }

    function townshipMatchesManaged(town, selected) {
        if (!selected) {
            return false;
        }
        var sel = normalizeName(selected);
        return sel === normalizeName(town.label)
            || sel === normalizeName(town.name)
            || sel === normalizeName(town.name_mm);
    }

    function finishTownshipSelect($township) {
        if ($township.hasClass('select2-hidden-accessible')) {
            $township.trigger('change.select2');
        } else {
            $township.trigger('change');
        }
    }

    function renderManagedTownships($township, towns, selected, preserveInvalidSelected, cityName) {
        var placeholder = $township.data('placeholder') || 'Select Township';
        var resolvedSelected = selected || String($township.data('pending-township') || '').trim() || '';
        var matchedValue = '';
        var selectedIsValid = !!(resolvedSelected && towns.some(function (town) {
            return townshipMatchesManaged(town, resolvedSelected);
        }));

        $township.empty().append(new Option(placeholder, '', false, false));

        towns.forEach(function (town) {
            var label = town.label || town.name_mm || town.name;
            var value = town.name_mm || town.name || label;
            var isSelected = selectedIsValid && townshipMatchesManaged(town, resolvedSelected);
            if (isSelected) {
                matchedValue = value;
            }
            appendTownshipOption($township, label, value, isSelected, town.deli_amount);
        });

        if (preserveInvalidSelected && selected && !selectedIsValid) {
            appendTownshipOption($township, selected, selected, true);
            matchedValue = selected;
        } else if (selected && !selectedIsValid) {
            appendTownshipOption($township, selected, selected, true);
            matchedValue = selected;
        }

        if (matchedValue) {
            $township.val(matchedValue);
            $township.removeData('pending-township');
        }

        finishTownshipSelect($township);
    }

    var townshipLoadSeq = 0;

    function loadManagedTownships(cityName, cityId, selected, townshipsUrl, preserveInvalidSelected, fallback) {
        var params = {};
        if (cityId) {
            params.city_id = cityId;
        } else if (cityName) {
            params.city = cityName;
        }

        var seq = ++townshipLoadSeq;
        $.getJSON(townshipsUrl, params).done(function (payload) {
            if (seq !== townshipLoadSeq) {
                return;
            }
            if (!payload || !payload.city) {
                fallback();
                return;
            }
            renderManagedTownships($('#item_township'), payload.townships || [], selected, preserveInvalidSelected, cityName);
        }).fail(function () {
            if (seq !== townshipLoadSeq) {
                return;
            }
            fallback();
        });
    }

    function loadTownships(cityName, nrcState, selected, nrcDataUrl, preserveInvalidSelected, extras) {
        extras = extras || {};
        var $township = $('#item_township');
        var placeholder = $township.data('placeholder') || 'Select Township';
        // Keep current selection visible until replacements arrive — clearing early makes
        // Select2 look filled while .val() is empty, which silently blocks Update.
        var keepSelected = String(selected || $township.val() || '').trim();
        if (keepSelected) {
            $township.data('pending-township', keepSelected);
        }

        if (!cityName) {
            $township.empty().append(new Option(placeholder, '', false, false));
            if (keepSelected) {
                $township.append(new Option(keepSelected, keepSelected, true, true));
            }
            finishTownshipSelect($township);
            return;
        }

        var townshipsUrl = extras.townshipsUrl || formDispatchOptions().townshipsUrl || '';
        if (townshipsUrl) {
            loadManagedTownships(cityName, extras.cityId, keepSelected || selected, townshipsUrl, preserveInvalidSelected, function () {
                loadNrcTownships(cityName, nrcState, keepSelected || selected, nrcDataUrl, preserveInvalidSelected);
            });
            return;
        }

        loadNrcTownships(cityName, nrcState, keepSelected || selected, nrcDataUrl, preserveInvalidSelected);
    }

    function loadNrcTownships(cityName, nrcState, selected, nrcDataUrl, preserveInvalidSelected) {
        var $township = $('#item_township');
        var placeholder = $township.data('placeholder') || 'Select Township';
        var normalizedCity = normalizeName(cityName);
        var normalizedState = normalizeName(nrcState);
        var resolvedSelected = selected || String($township.data('pending-township') || '').trim() || '';

        $.getJSON(nrcDataUrl).done(function (data) {
            var states = data.states || [];
            var matchedState = null;

            if (normalizedState) {
                matchedState = states.find(function (state) {
                    var stateEn = normalizeName(state.name_en);
                    var stateMm = normalizeName(state.name_mm);
                    return stateEn === normalizedState || stateMm === normalizedState;
                }) || null;
            }

            if (!matchedState) {
                states.forEach(function (state) {
                    (state.townships || []).forEach(function (town) {
                        var townEn = normalizeName(town.name_en);
                        var townMm = normalizeName(town.name_mm);
                        if (namesMatch(townEn, normalizedCity) || namesMatch(townMm, normalizedCity)) {
                            matchedState = state;
                        }
                    });
                });
            }

            if (!matchedState) {
                matchedState = states.find(function (state) {
                    var stateEn = normalizeName(state.name_en);
                    var stateMm = normalizeName(state.name_mm);
                    return namesMatch(stateEn, normalizedCity) || namesMatch(stateMm, normalizedCity);
                }) || null;
            }

            var selectedIsValid = false;
            if (resolvedSelected && matchedState && matchedState.townships) {
                selectedIsValid = matchedState.townships.some(function (town) {
                    return townshipMatchesSelection(town, resolvedSelected);
                });
            }

            var selectedValue = resolvedSelected;
            if (!selectedIsValid) {
                selectedValue = '';
            }

            $township.empty().append(new Option(placeholder, '', false, false));

            if (matchedState && matchedState.townships && matchedState.townships.length) {
                matchedState.townships.forEach(function (town) {
                    var label = town.name_mm || town.name_en;
                    var value = label;
                    var isSelected = !!(selectedValue && townshipMatchesSelection(town, selectedValue));
                    appendTownshipOption($township, label, value, isSelected);
                });
            }

            if (resolvedSelected && !selectedIsValid) {
                appendTownshipOption($township, resolvedSelected, resolvedSelected, true);
            }

            if (String($township.val() || '').trim()) {
                $township.removeData('pending-township');
            }

            finishTownshipSelect($township);
        }).fail(function () {
            if (resolvedSelected && !String($township.val() || '').trim()) {
                ensureSelectValue($township, resolvedSelected);
            }
            finishTownshipSelect($township);
        });
    }

    function getSelectedCityMeta() {
        var $selected = $('#item_delivery_city option:selected');
        var value = String($selected.val() || '').trim();
        if (!value) {
            return null;
        }

        return {
            id: String($selected.attr('data-city-id') || '').trim(),
            name: String($selected.attr('data-name') || $selected.text() || '').trim(),
            nrcState: String($selected.attr('data-nrc-state') || '').trim()
        };
    }

    function resetTownshipSelect() {
        var $township = $('#item_township');
        var placeholder = $township.data('placeholder') || 'Select Township';
        $township.empty().append(new Option(placeholder, '', false, false));
        if ($township.hasClass('select2-hidden-accessible')) {
            $township.trigger('change.select2');
        } else {
            $township.trigger('change');
        }
    }

    function appendAndSelectOption($select, label, value, attrs) {
        if (!value) {
            return false;
        }

        var exists = $select.find('option').filter(function () {
            return $(this).val() === value;
        }).length > 0;

        if (!exists) {
            var option = new Option(label, value, true, true);
            if (attrs) {
                Object.keys(attrs).forEach(function (key) {
                    option.setAttribute(key, attrs[key]);
                });
            }
            $select.append(option);
        } else {
            $select.val(value);
        }

        if ($select.hasClass('select2-hidden-accessible')) {
            $select.trigger('change.select2');
        } else {
            $select.trigger('change');
        }

        return true;
    }

    function findOptionByText($select, text) {
        var normalized = normalizeName(text);
        var matched = null;

        $select.find('option').each(function () {
            var $option = $(this);
            var optionValue = String($option.val() || '').trim();
            if (!optionValue) {
                return;
            }

            var optionText = String($option.text() || '').trim();
            var customName = optionValue.indexOf('custom:') === 0 ? optionValue.slice(7) : '';

            if (normalizeName(optionText) === normalized
                || normalizeName(optionValue) === normalized
                || (customName && normalizeName(customName) === normalized)) {
                matched = $option;
                return false;
            }
        });

        return matched;
    }

    function notifySuccess(message) {
        if (typeof showMessage === 'function') {
            showMessage(message);
        } else if (typeof successMessage === 'function') {
            successMessage(message);
        } else if (typeof Snackbar !== 'undefined' && Snackbar.show) {
            Snackbar.show({ text: message, pos: 'bottom-center', showAction: false, duration: 4000 });
        } else {
            window.alert(message);
        }
    }

    function notifyError(message) {
        message = String(message || 'Unable to save item.');
        try {
            if (typeof errorMessage === 'function') {
                errorMessage(message);
            } else if (typeof Snackbar !== 'undefined' && Snackbar.show) {
                Snackbar.show({
                    text: message,
                    pos: 'bottom-center',
                    backgroundColor: '#dc3545',
                    textColor: '#ffffff',
                    showAction: false,
                    duration: 4000
                });
            } else {
                window.alert(message);
            }
        } catch (err) {
            window.alert(message);
        }
        // Ensure feedback is never hidden behind the photo modal.
        window.setTimeout(function () {
            var el = document.querySelector('.snackbar-container');
            if (el) {
                el.style.zIndex = '20000';
            }
        }, 0);
    }

    function persistManagedLocation(url, data, onSuccess, onFail) {
        if (!url) {
            onFail();
            return;
        }

        $.ajax({
            url: url,
            method: 'POST',
            headers: csrfHeaders(),
            data: data
        }).done(function (res) {
            if (res && res.message) {
                notifySuccess(res.message);
            }
            onSuccess(res || {});
        }).fail(function (xhr) {
            notifyError(extractErrorMessage(xhr));
            onFail();
        });
    }

    function initDispatchFieldAddButtons($scope) {
        $scope = $scope && $scope.length ? $scope : $(document);
        $scope.off('click.dispatchItemAdd', '.pds-dispatch-field-add')
            .on('click.dispatchItemAdd', '.pds-dispatch-field-add', function (e) {
                e.preventDefault();
                e.stopPropagation();

                var $btn = $(this);
                var $select = $($btn.data('dispatch-add-target'));
                var type = String($btn.data('dispatch-add-type') || '').trim();
                var promptText = $btn.data('dispatch-add-prompt') || 'Enter value';

                if (!$select.length) {
                    return;
                }

                var raw = window.prompt(promptText, '');
                if (raw === null) {
                    return;
                }

                var value = String(raw).trim();
                if (!value) {
                    notifyError('Value is required.');
                    return;
                }

                if (type === 'branch') {
                    var $existingBranch = findOptionByText($select, value);
                    if ($existingBranch) {
                        $select.val($existingBranch.val()).trigger('change.select2');
                        return;
                    }

                    persistManagedLocation(formDispatchOptions().branchesStoreUrl, { name: value }, function (res) {
                        var branch = (res && res.branch) || {};
                        var branchId = branch.id || ('custom:' + value);
                        var branchName = branch.name || value;
                        appendAndSelectOption($select, branchName, String(branchId), {
                            'data-city-name': branch.city_name || branchName
                        });
                    }, function () {
                        appendAndSelectOption($select, value, 'custom:' + value);
                    });
                    return;
                }

                if (type === 'city') {
                    var $existingCity = findOptionByText($select, value);
                    if ($existingCity) {
                        $select.val($existingCity.val()).trigger('change.select2');
                        $('#item_delivery_city').trigger('change.dispatchItemCity');
                        return;
                    }

                    persistManagedLocation(formDispatchOptions().citiesStoreUrl, { name: value, name_mm: value }, function (res) {
                        var city = (res && res.city) || {};
                        var cityName = city.name || value;
                        appendAndSelectOption($select, city.label || cityName, cityName, {
                            'data-name': cityName,
                            'data-city-id': city.id || '',
                            'data-nrc-state': cityName
                        });
                        $('#item_delivery_city').trigger('change.dispatchItemCity');
                    }, function () {
                        appendAndSelectOption($select, value, value, {
                            'data-name': value,
                            'data-city-id': '',
                            'data-nrc-state': ''
                        });
                    });
                    return;
                }

                if (type === 'township') {
                    var $existingTownship = findOptionByText($select, value);
                    if ($existingTownship) {
                        $select.val($existingTownship.val()).trigger('change.select2');
                        return;
                    }

                    var cityMeta = getSelectedCityMeta();
                    var townshipPayload = { name: value, name_mm: value };
                    if (cityMeta && cityMeta.id) {
                        townshipPayload.delivery_city_id = cityMeta.id;
                    }

                    persistManagedLocation(
                        (cityMeta && cityMeta.id) ? formDispatchOptions().townshipsStoreUrl : '',
                        townshipPayload,
                        function (res) {
                            var township = (res && res.township) || {};
                            appendAndSelectOption($select, township.label || value, township.name_mm || township.name || value, {
                                'data-deli-amount': township.deli_amount
                            });
                        },
                        function () {
                            appendAndSelectOption($select, value, value);
                        }
                    );
                }
            });
    }

    function extractErrorMessage(xhr) {
        if (!xhr || !xhr.responseJSON) {
            return 'Unable to save item.';
        }

        var payload = xhr.responseJSON;
        if (payload.message) {
            return payload.message;
        }

        if (payload.errors) {
            return Object.values(payload.errors).flat().join('\n');
        }

        return 'Unable to save item.';
    }

    window.reloadDispatchItemsTable = function () {
        var table = null;

        if (window.LaravelDataTables) {
            table = window.LaravelDataTables.dataTableBuilder
                || window.LaravelDataTables['dataTableBuilder'];
        }

        if (!table && window.jQuery && $.fn.DataTable && $('#dataTableBuilder').length) {
            try {
                table = $('#dataTableBuilder').DataTable();
            } catch (e) {
                table = null;
            }
        }

        if (table && typeof table.ajax === 'object' && typeof table.ajax.reload === 'function') {
            table.ajax.reload(function () {
                $('#dataTableBuilder').trigger('draw.dt');
            }, false);
        }
    };

    function bindDispatchItemFormSubmit() {
        // Always (re)bind — sticky flag used to leave Update dead after modal remounts.
        window.__dispatchItemFormSubmitBound = true;

        $(document).off('submit.dispatchItem', '#dispatch_item_form')
            .on('submit.dispatchItem', '#dispatch_item_form', function (e) {
                e.preventDefault();
                e.stopPropagation();
                submitDispatchItemForm($(this));
            });

        $(document).off('click.dispatchItemSave', '#dispatch_item_form [type="submit"], #dispatch_item_form [data-dispatch-item-save]')
            .on('click.dispatchItemSave', '#dispatch_item_form [type="submit"], #dispatch_item_form [data-dispatch-item-save]', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var $form = $(this).closest('#dispatch_item_form');
                if ($form.length) {
                    submitDispatchItemForm($form);
                }
            });
    }

    function submitDispatchItemForm($form) {
        try {
            if (!$form || !$form.length) {
                return;
            }
            if ($form.data('dispatchSaving')) {
                return;
            }

            var $modal = $form.closest('#remoteModelData');
            var savedTownship = String(
                $form.attr('data-saved-township')
                || $form.data('savedTownship')
                || $form.data('pending-township')
                || ''
            ).trim();

            var receivedDate = resolveSelectValue($form.find('#item_received_date'), []);
            var fromBranch = resolveSelectValue($form.find('#from_branch_id'), []);
            var toBranch = resolveSelectValue($form.find('#to_branch_id'), []);
            var deliveryCity = resolveSelectValue($form.find('#item_delivery_city'), [
                $form.attr('data-default-delivery-city')
            ]);
            var township = resolveSelectValue($form.find('#item_township'), [
                savedTownship,
                $form.find('#item_township').data('pending-township')
            ]);

            if (township) {
                township = ensureSelectValue($form.find('#item_township'), township);
            }
            if (deliveryCity) {
                deliveryCity = ensureSelectValue($form.find('#item_delivery_city'), deliveryCity);
            }

            if (!receivedDate) {
                notifyError('Received date is required.');
                return;
            }
            if (!fromBranch) {
                notifyError('From branch is required.');
                return;
            }
            if (!toBranch) {
                notifyError('To branch is required.');
                return;
            }
            if (!deliveryCity) {
                notifyError('City is required.');
                return;
            }
            if (!township) {
                notifyError('Township is required.');
                return;
            }

            var formData = new FormData($form.get(0));
            formData.set('received_date', receivedDate);
            formData.set('from_branch_id', fromBranch);
            formData.set('to_branch_id', toBranch);
            formData.set('delivery_city', deliveryCity);
            formData.set('township', township);
            formData.set('item_name', String($form.find('#item_name').val() || '').trim());
            formData.set('remark', String($form.find('#item_remark').val() || '').trim());
            formData.set('customer_phone', String($form.find('#customer_phone').val() || '').trim());

            var rotation = 0;
            if (window.PdsPhotoZoom && typeof window.PdsPhotoZoom.readInlineRotation === 'function') {
                rotation = window.PdsPhotoZoom.readInlineRotation();
            } else if (typeof window.__pdsInlinePhotoRotation === 'number') {
                rotation = window.__pdsInlinePhotoRotation;
            } else {
                rotation = parseInt($form.find('[name="photo_rotation"]').val(), 10) || 0;
            }
            rotation = ((rotation % 360) + 360) % 360;
            formData.set('photo_rotation', String(rotation));
            $form.find('[name="photo_rotation"]').val(String(rotation));

            // UI modes: os | customer | os_paid → backend credit_to is only os|customer.
            if (isOsPaidMode($form)) {
                formData.set('credit_to', 'os');
                syncOsPaidWithDeliAmount(true);
                formData.set('os_paid', String(Math.round(parseFloat($form.find('#os_paid').val()) || 0)));
            } else if (getCreditMode($form) === 'os') {
                formData.set('credit_to', 'os');
                formData.set('os_paid', '0');
            } else {
                formData.set('credit_to', 'customer');
                formData.set('os_paid', '0');
            }

            var spoofMethod = String($form.find('input[name="_method"]').val() || '').toUpperCase();
            if (spoofMethod && spoofMethod !== 'POST') {
                formData.set('_method', spoofMethod);
            }

            var $submitBtn = $form.find('[type="submit"], [data-dispatch-item-save]');
            $form.data('dispatchSaving', true);
            $submitBtn.prop('disabled', true);

            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                success: function (res) {
                    if (res && res.message) {
                        notifySuccess(res.message);
                    }

                    // Hide first while CSS rotation is still applied — resetting/src-swapping
                    // in a visible modal flashes the unrotated cached frame.
                    if ($modal.length) {
                        $modal.one('hidden.bs.modal.pdsPhotoRotate', function () {
                            if (typeof window.__pdsResetInlinePhotoRotation === 'function') {
                                window.__pdsResetInlinePhotoRotation();
                            }
                            var img = document.getElementById('pdsItemInlinePhotoImg');
                            if (img && res && res.photo_url) {
                                img.removeAttribute('src');
                            }
                        });
                        $modal.modal('hide');
                    } else if (typeof window.__pdsResetInlinePhotoRotation === 'function') {
                        window.__pdsResetInlinePhotoRotation();
                    }

                    if (res && res.moved_to_assign_100 && res.redirect) {
                        window.setTimeout(function () {
                            window.location.href = res.redirect;
                        }, 600);
                        return;
                    }
                    if (res && res.moved_to_admin_done && res.redirect) {
                        window.setTimeout(function () {
                            window.location.href = res.redirect;
                        }, 600);
                        return;
                    }
                    if (res && res.to_branch_id) {
                        var nextBranch = String(res.to_branch_id);
                        var branchNode = document.querySelector('[data-active-to-branch]');
                        var currentBranch = branchNode ? String(branchNode.getAttribute('data-active-to-branch') || '') : '';
                        if (nextBranch && currentBranch && nextBranch !== currentBranch) {
                            var nextUrl = new URL(window.location.href);
                            nextUrl.searchParams.set('to_branch_id', nextBranch);
                            window.location.href = nextUrl.toString();
                            return;
                        }
                    }
                    // Slight delay so list thumbs pick up cache-busted URLs after rotate.
                    window.setTimeout(function () {
                        window.reloadDispatchItemsTable();
                    }, res && res.photo_rotated ? 150 : 0);
                },
                error: function (xhr) {
                    notifyError(extractErrorMessage(xhr));
                },
                complete: function () {
                    $form.data('dispatchSaving', false);
                    $submitBtn.prop('disabled', false);
                }
            });
        } catch (err) {
            if ($form && $form.length) {
                $form.data('dispatchSaving', false);
                $form.find('[type="submit"], [data-dispatch-item-save]').prop('disabled', false);
            }
            notifyError((err && err.message) ? err.message : 'Unable to save item.');
        }
    }

    window.__pdsSaveDispatchItem = function (event) {
        if (event && typeof event.preventDefault === 'function') {
            event.preventDefault();
        }
        var $form = $('#dispatch_item_form');
        if (!$form.length && event && event.target) {
            $form = $(event.target).closest('#dispatch_item_form');
        }
        submitDispatchItemForm($form);
        return false;
    };

    window.initDispatchItemForm = function (options) {
        options = options || {};
        bindDispatchItemFormSubmit();

        var $form = $('#dispatch_item_form');
        if (!$form.length) {
            return;
        }

        $form.data('dispatch-item-options', options);

        var $modal = $(options.modalParent || '#remoteModelData');
        var dropdownParent = $modal.length ? $modal : $(document.body);
        var savedTownship = String(
            options.savedTownship
            || $form.data('savedTownship')
            || $form.attr('data-saved-township')
            || $('#item_township').val()
            || ''
        ).trim();
        var isInitializing = true;

        $('#item_township').data('placeholder', $('#item_township option:first').text());

        $('.dispatch-item-select2').each(function () {
            var $el = $(this);
            if ($el.hasClass('select2-hidden-accessible')) {
                $el.select2('destroy');
            }
            $el.select2({
                width: '100%',
                dropdownParent: dropdownParent,
                minimumResultsForSearch: 0
            });
        });

        if ($.fn.datepicker) {
            $('.dispatch-item-datepicker').each(function () {
                var $input = $(this);
                if ($input.hasClass('hasDatepicker')) {
                    $input.datepicker('destroy');
                }
                $input.datepicker({ dateFormat: 'dd-mm-yy', changeMonth: true, changeYear: true });
                if ($input.val()) {
                    $input.datepicker('setDate', $input.val());
                }
            });
        }

        var preserveSavedDeli = (parseFloat($('#deli_amount').val()) || 0) > 0;

        $('#item_township')
            .off('change.dispatchItemTownshipDeli select2:select.dispatchItemTownshipDeli')
            .on('select2:select.dispatchItemTownshipDeli', function () {
                preserveSavedDeli = false;
                savedTownship = String($(this).val() || '').trim();
                applyTownshipDeliAmount();
            })
            .on('change.dispatchItemTownshipDeli', function () {
                if (preserveSavedDeli) {
                    return;
                }
                applyTownshipDeliAmount();
            });

        function refreshTownships(resetTownship) {
            var cityMeta = getSelectedCityMeta();
            if (!cityMeta) {
                resetTownshipSelect();
                return;
            }

            if (resetTownship) {
                savedTownship = '';
            }

            var selected = resetTownship
                ? ''
                : (String($('#item_township').val() || '').trim() || savedTownship);
            loadTownships(cityMeta.name, cityMeta.nrcState, selected, options.nrcDataUrl, !resetTownship, {
                townshipsUrl: options.townshipsUrl,
                cityId: cityMeta.id
            });
        }

        $('#item_delivery_city').off('change.dispatchItemCity').on('change.dispatchItemCity', function () {
            if (isInitializing) {
                return;
            }
            preserveSavedDeli = false;
            refreshTownships(true);
        });

        var toCityName = String($('#to_branch_id option:selected').data('city-name') || '').trim();
        var currentCity = String($('#item_delivery_city').val() || '').trim();
        var cityLooksMdyDefault = !currentCity || isMandalayName(currentCity);
        var toIsDifferentCity = toCityName
            && !samePlaceName(toCityName, currentCity || 'Mandalay')
            && !(cityLooksMdyDefault && isMandalayName(toCityName));
        if (toIsDifferentCity && cityLooksMdyDefault) {
            syncCityFromToBranch(false);
        } else {
            refreshTownships(false);
        }
        isInitializing = false;

        function syncCityFromToBranch(resetTownship) {
            var $opt = $('#to_branch_id option:selected');
            var cityName = String($opt.data('city-name') || $opt.text() || '').trim();
            if (!cityName) {
                return;
            }
            var $city = $('#item_delivery_city');
            var previousCity = String($city.val() || '').trim();
            var $matched = findOptionByText($city, cityName);
            if ($matched) {
                $city.val($matched.val());
            } else {
                appendAndSelectOption($city, cityName, cityName, {
                    'data-name': cityName,
                    'data-city-id': '',
                    'data-nrc-state': cityName
                });
            }
            var nextCity = String($city.val() || '').trim();
            var cityChanged = !samePlaceName(previousCity, nextCity);
            if ($city.hasClass('select2-hidden-accessible')) {
                $city.trigger('change.select2');
            } else {
                $city.trigger('change');
            }
            refreshTownships(!!resetTownship && cityChanged);
        }

        $('#to_branch_id').off('change.dispatchItemTo').on('change.dispatchItemTo', function () {
            if (isInitializing) {
                return;
            }
            preserveSavedDeli = false;
            syncCityFromToBranch(true);
        });

        $(document).off('input.dispatchItemAmount', '.pds-dispatch-item-amount')
            .on('input.dispatchItemAmount', '.pds-dispatch-item-amount', function () {
                // Size-gate handler already syncs OsPaid + summary for #deli_amount.
                if (this.id === 'deli_amount') {
                    return;
                }
                updateSummary();
            });

        $('input[name="credit_to"]').off('change.dispatchItemCredit')
            .on('change.dispatchItemCredit', toggleOsPaidField);

        applyOsCreditRestrictions();
        toggleOsPaidField();
        initDispatchItemSizeSelector();

        initDispatchFieldAddButtons($modal);
    };
})(window, jQuery);
