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

    function getCreditTo() {
        return $('input[name="credit_to"]:checked').val() || 'customer';
    }

    function updateSummary() {
        var itemValue = parseFloat($('#item_value').val()) || 0;
        var deliAmount = parseFloat($('#deli_amount').val()) || 0;
        var osPaid = parseFloat($('#os_paid').val()) || 0;
        var creditTo = getCreditTo();
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
        var creditTo = getCreditTo();
        var $wrap = $('#os_paid_field_wrap');

        if (creditTo === 'customer') {
            $wrap.hide();
            $('#os_paid').val(0);
        } else {
            $wrap.show();
        }

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
            .on('input.dispatchItemSizeGate change.dispatchItemSizeGate', '#deli_amount', syncDispatchItemSizeAvailability);

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
        var $customerRadio = $('input[name="credit_to"][value="customer"]');

        $osRadio.prop('disabled', true);
        $osRadio.closest('.pds-dispatch-item-credit-pill').addClass('is-disabled');

        if ($osRadio.is(':checked')) {
            $customerRadio.prop('checked', true);
        }

        toggleOsPaidField();
    }

    function appendTownshipOption($township, label, value, selected) {
        if (!label) {
            return;
        }
        var exists = $township.find('option').filter(function () {
            return $(this).val() === value;
        }).length > 0;
        if (exists) {
            if (selected) {
                $township.val(value);
            }
            return;
        }
        $township.append(new Option(label, value, selected, selected));
    }

    function townshipMatchesSelection(town, selected) {
        var label = town.name_mm || town.name_en;
        return selected === label || selected === town.name_en || selected === town.name_mm;
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
        return selected === town.label
            || selected === town.name
            || selected === town.name_mm;
    }

    function finishTownshipSelect($township) {
        if ($township.hasClass('select2-hidden-accessible')) {
            $township.trigger('change.select2');
        } else {
            $township.trigger('change');
        }
    }

    function renderManagedTownships($township, towns, selected, preserveInvalidSelected, cityName) {
        var resolvedSelected = selected || '';
        var selectedIsValid = !!(resolvedSelected && towns.some(function (town) {
            return townshipMatchesManaged(town, resolvedSelected);
        }));

        if (!selectedIsValid) {
            resolvedSelected = '';
            var $form = $('#dispatch_item_form');
            if ($form.data('apply-default-township')) {
                var defaultCity = normalizeName($form.data('default-delivery-city') || 'Mandalay');
                var defaultTownship = String($form.data('default-township') || '').trim();
                if (defaultTownship && normalizeName(cityName) === defaultCity) {
                    resolvedSelected = defaultTownship;
                }
            }
        }

        towns.forEach(function (town) {
            var label = town.label || town.name_mm || town.name;
            var value = town.name_mm || town.name || label;
            appendTownshipOption($township, label, value, resolvedSelected ? townshipMatchesManaged(town, resolvedSelected) : false);
        });

        if (!$township.val()) {
            var $firstRealOption = $township.find('option').filter(function () {
                return String($(this).val() || '').trim() !== '';
            }).first();
            if ($firstRealOption.length) {
                $township.val($firstRealOption.val());
            }
        }

        if (preserveInvalidSelected && selected && !selectedIsValid) {
            appendTownshipOption($township, selected, selected, true);
        }

        finishTownshipSelect($township);
    }

    function loadManagedTownships(cityName, cityId, selected, townshipsUrl, preserveInvalidSelected, fallback) {
        var params = {};
        if (cityId) {
            params.city_id = cityId;
        } else if (cityName) {
            params.city = cityName;
        }

        $.getJSON(townshipsUrl, params).done(function (payload) {
            if (!payload || !payload.city) {
                fallback();
                return;
            }
            renderManagedTownships($('#item_township'), payload.townships || [], selected, preserveInvalidSelected, cityName);
        }).fail(function () {
            fallback();
        });
    }

    function loadTownships(cityName, nrcState, selected, nrcDataUrl, preserveInvalidSelected, extras) {
        extras = extras || {};
        var $township = $('#item_township');
        var placeholder = $township.data('placeholder') || 'Select Township';
        $township.empty().append(new Option(placeholder, '', false, false));

        if (!cityName) {
            $township.trigger('change');
            return;
        }

        var townshipsUrl = extras.townshipsUrl || formDispatchOptions().townshipsUrl || '';
        if (townshipsUrl) {
            loadManagedTownships(cityName, extras.cityId, selected, townshipsUrl, preserveInvalidSelected, function () {
                loadNrcTownships(cityName, nrcState, selected, nrcDataUrl, preserveInvalidSelected);
            });
            return;
        }

        loadNrcTownships(cityName, nrcState, selected, nrcDataUrl, preserveInvalidSelected);
    }

    function loadNrcTownships(cityName, nrcState, selected, nrcDataUrl, preserveInvalidSelected) {
        var $township = $('#item_township');
        var normalizedCity = normalizeName(cityName);
        var normalizedState = normalizeName(nrcState);
        var resolvedSelected = selected || '';
        var $form = $('#dispatch_item_form');

        $.getJSON(nrcDataUrl).done(function (data) {
            var states = data.states || [];
            var matchedState = null;
            var matchedTownship = null;

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
                            matchedTownship = town;
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

            if (!selectedIsValid) {
                resolvedSelected = '';
                if ($form.data('apply-default-township')) {
                    var defaultCity = normalizeName($form.data('default-delivery-city') || 'Mandalay');
                    var defaultTownship = String($form.data('default-township') || '').trim();
                    if (defaultTownship && normalizedCity === defaultCity) {
                        resolvedSelected = defaultTownship;
                    }
                }
            }

            if (matchedState && matchedState.townships && matchedState.townships.length) {
                matchedState.townships.forEach(function (town) {
                    var label = town.name_mm || town.name_en;
                    var value = label;
                    var isSelected = resolvedSelected
                        ? townshipMatchesSelection(town, resolvedSelected)
                        : !!(matchedTownship && town.name_en === matchedTownship.name_en);
                    appendTownshipOption($township, label, value, isSelected);
                });
            }

            if (!$township.val()) {
                var $firstRealOption = $township.find('option').filter(function () {
                    return String($(this).val() || '').trim() !== '';
                }).first();
                if ($firstRealOption.length) {
                    $township.val($firstRealOption.val());
                }
            }

            if (preserveInvalidSelected && selected && !selectedIsValid) {
                appendTownshipOption($township, selected, selected, true);
            }

            if ($township.hasClass('select2-hidden-accessible')) {
                $township.trigger('change.select2');
            } else {
                $township.trigger('change');
            }
        }).fail(function () {
            if (cityName) {
                appendTownshipOption($township, cityName, cityName, true);
            }
            if ($township.hasClass('select2-hidden-accessible')) {
                $township.trigger('change.select2');
            } else {
                $township.trigger('change');
            }
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

    function ensureTownshipValue() {
        var $township = $('#item_township');
        if ($township.val()) {
            return;
        }
        var cityMeta = getSelectedCityMeta();
        if (cityMeta && cityMeta.name) {
            appendTownshipOption($township, cityMeta.name, cityMeta.name, true);
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
        }
    }

    function notifyError(message) {
        if (typeof errorMessage === 'function') {
            errorMessage(message);
        }
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
                            appendAndSelectOption($select, township.label || value, township.name_mm || township.name || value);
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
        if (window.__dispatchItemFormSubmitBound) {
            return;
        }
        window.__dispatchItemFormSubmitBound = true;

        $(document).off('submit.dispatchItem', '#dispatch_item_form')
            .on('submit.dispatchItem', '#dispatch_item_form', function (e) {
                e.preventDefault();

                var $form = $(this);
                var $modal = $form.closest('#remoteModelData');
                var options = $form.data('dispatch-item-options') || {};
                ensureTownshipValue();

                var requiredFields = [
                    { selector: '#item_received_date', message: 'Received date is required.' },
                    { selector: '#from_branch_id', message: 'From branch is required.' },
                    { selector: '#to_branch_id', message: 'To branch is required.' },
                    { selector: '#item_delivery_city', message: 'City is required.' },
                    { selector: '#item_township', message: 'Township is required.' }
                ];

                for (var i = 0; i < requiredFields.length; i++) {
                    var $field = $form.find(requiredFields[i].selector);
                    if (!$field.length || !String($field.val() || '').trim()) {
                        notifyError(requiredFields[i].message);
                        return;
                    }
                }

                var formData = new FormData(this);
                formData.set('item_name', String($form.find('#item_name').val() || '').trim());
                formData.set('remark', String($form.find('#item_remark').val() || '').trim());
                formData.set('customer_phone', String($form.find('#customer_phone').val() || '').trim());
                if (getCreditTo() === 'customer') {
                    formData.set('os_paid', '0');
                }

                var spoofMethod = String($form.find('input[name="_method"]').val() || '').toUpperCase();
                if (spoofMethod && spoofMethod !== 'POST') {
                    formData.set('_method', spoofMethod);
                }

                var $submitBtn = $form.find('[type="submit"]');
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
                        if ($modal.length) {
                            $modal.modal('hide');
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
                        window.reloadDispatchItemsTable();
                    },
                    error: function (xhr) {
                        notifyError(extractErrorMessage(xhr));
                    },
                    complete: function () {
                        $submitBtn.prop('disabled', false);
                    }
                });
            });
    }

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

        function refreshTownships(resetTownship) {
            var cityMeta = getSelectedCityMeta();
            if (!cityMeta) {
                resetTownshipSelect();
                return;
            }

            var selected = resetTownship ? '' : $('#item_township').val();
            loadTownships(cityMeta.name, cityMeta.nrcState, selected, options.nrcDataUrl, !resetTownship, {
                townshipsUrl: options.townshipsUrl,
                cityId: cityMeta.id
            });
        }

        $('#item_delivery_city').off('change.dispatchItemCity').on('change.dispatchItemCity', function () {
            refreshTownships(true);
        });
        refreshTownships(false);

        function syncCityFromToBranch(resetTownship) {
            var $opt = $('#to_branch_id option:selected');
            var cityName = String($opt.data('city-name') || $opt.text() || '').trim();
            if (!cityName) {
                return;
            }
            var $city = $('#item_delivery_city');
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
            if ($city.hasClass('select2-hidden-accessible')) {
                $city.trigger('change.select2');
            } else {
                $city.trigger('change');
            }
            refreshTownships(!!resetTownship);
        }

        $('#to_branch_id').off('change.dispatchItemTo').on('change.dispatchItemTo', function () {
            syncCityFromToBranch(true);
        });

        $(document).off('input.dispatchItemAmount', '.pds-dispatch-item-amount')
            .on('input.dispatchItemAmount', '.pds-dispatch-item-amount', updateSummary);

        $('input[name="credit_to"]').off('change.dispatchItemCredit')
            .on('change.dispatchItemCredit', toggleOsPaidField);

        applyOsCreditRestrictions();
        toggleOsPaidField();
        initDispatchItemSizeSelector();

        initDispatchFieldAddButtons($modal);
    };
})(window, jQuery);
