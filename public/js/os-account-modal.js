(function (window, $) {
    'use strict';

    var osAccountXhr = null;

    function getForm() {
        return $('#os_account_form');
    }

    function getSaveButton() {
        return $('#os_account_save_btn');
    }

    function getModalRoot() {
        return $('.pds-os-account-modal');
    }

    function closeOsDropdowns() {
        if (!$.fn.select2) {
            return;
        }
        $('#os_city_id, #os_nrc_region, #os_nrc_town, #os_nrc_type, #reg_state, #reg_township').each(function () {
            var $field = $(this);
            if ($field.hasClass('select2-hidden-accessible')) {
                try {
                    $field.select2('close');
                } catch (e) {}
            }
        });
    }

    function showOsFormError(message) {
        var $error = $('#os_account_form_error');
        if (!$error.length) {
            if (typeof errorMessage === 'function') {
                errorMessage(message);
            }
            return;
        }
        if (!message) {
            $error.attr('hidden', true).text('');
            return;
        }
        $error.removeAttr('hidden').text(message);
        if (typeof errorMessage === 'function') {
            errorMessage(message);
        }
    }

    function resetSaveButton() {
        var $btn = getSaveButton();
        if (!$btn.length) {
            return;
        }
        $btn.prop('disabled', false).removeClass('disabled is-saving').attr('aria-busy', 'false');
    }

    function setSavingState(isSaving) {
        var $btn = getSaveButton();
        if (!$btn.length) {
            return;
        }
        if (isSaving) {
            $btn.addClass('is-saving').attr('aria-busy', 'true');
        } else {
            resetSaveButton();
        }
    }

    function scrollToInvalidField($form, $field) {
        if (!$field.length) {
            return;
        }
        closeOsDropdowns();
        var $scroll = $form.find('.pds-os-account-modal-body');
        if ($scroll.length) {
            var fieldTop = $field.offset().top;
            var scrollTop = $scroll.offset().top;
            var nextTop = $scroll.scrollTop() + (fieldTop - scrollTop) - 24;
            $scroll.animate({ scrollTop: Math.max(0, nextTop) }, 200);
        }
        window.setTimeout(function () {
            if ($field.hasClass('select2-hidden-accessible') && $.fn.select2) {
                try {
                    $field.select2('open');
                } catch (e) {
                    $field.trigger('focus');
                }
            } else {
                $field.trigger('focus');
            }
        }, 220);
    }

    function fieldValue($field) {
        if (!$field.length) {
            return '';
        }
        return ($field.val() || '').toString().trim();
    }

    function syncOsRegPhone() {
        var $phone = getForm().find('#phone[data-user-reg-phone]');
        var $hidden = getForm().find('#contact_number_hidden');
        if (!$phone.length || !$hidden.length) {
            return;
        }
        var digits = String($phone.val() || '').replace(/\D/g, '');
        while (digits.indexOf('95') === 0 && digits.length >= 12) {
            digits = digits.slice(2);
        }
        if (/^0\d+/.test(digits)) {
            digits = digits.replace(/^0+/, '');
        }
        if (/^9\d{7,9}$/.test(digits)) {
            $hidden.val('+95' + digits);
            return;
        }
        if (digits) {
            $hidden.val(digits.indexOf('95') === 0 ? '+' + digits : digits);
        }
    }

    function validateOsAccountForm($form) {
        var checks = [
            { selector: '#reg_username', message: $form.attr('data-msg-login') },
            { selector: '#reg_password', message: $form.attr('data-msg-password') },
            { selector: '#reg_password_confirmation', message: $form.attr('data-msg-password-confirm') },
            { selector: '#reg_name', message: $form.attr('data-msg-os-name') },
            { selector: '#phone', message: $form.attr('data-msg-phone') },
            { selector: '#reg_address_unit', message: $form.attr('data-msg-address') },
            { selector: '#reg_state', message: $form.attr('data-msg-state') },
            { selector: '#reg_township', message: $form.attr('data-msg-township') },
            { selector: '#reg_kpay_name', message: $form.attr('data-msg-kpay-name') },
            { selector: '#reg_kpay_no', message: $form.attr('data-msg-kpay-no') }
        ];

        for (var i = 0; i < checks.length; i++) {
            var $field = $form.find(checks[i].selector);
            if (!fieldValue($field)) {
                showOsFormError(checks[i].message);
                scrollToInvalidField($form, $field);
                return false;
            }
        }

        if (fieldValue($form.find('#reg_password')).length < 6) {
            showOsFormError($form.attr('data-msg-password-length') || 'Password must be at least 6 characters');
            scrollToInvalidField($form, $form.find('#reg_password'));
            return false;
        }

        if (fieldValue($form.find('#reg_password')) !== fieldValue($form.find('#reg_password_confirmation'))) {
            showOsFormError($form.attr('data-msg-password-match') || 'Passwords do not match');
            scrollToInvalidField($form, $form.find('#reg_password_confirmation'));
            return false;
        }

        syncOsRegPhone();
        showOsFormError('');
        return true;
    }

    function submitOsAccountForm() {
        var $form = getForm();
        var $btn = getSaveButton();
        if (!$form.length || !$btn.length) {
            return;
        }

        closeOsDropdowns();

        if (!validateOsAccountForm($form)) {
            resetSaveButton();
            return;
        }

        if (osAccountXhr && osAccountXhr.readyState !== 4) {
            osAccountXhr.abort();
        }

        setSavingState(true);

        osAccountXhr = $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: new FormData($form[0]),
            processData: false,
            contentType: false,
            timeout: 60000,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            success: function (res) {
                if (res.status) {
                    showOsFormError('');
                    if (typeof showMessage === 'function') {
                        showMessage(res.message);
                    }
                    closeOsDropdowns();
                    var savedUser = res.user || null;
                    $('#remoteModelData').modal('hide');
                    if (savedUser) {
                        if (typeof window.fillDispatchOsFields === 'function') {
                            window.fillDispatchOsFields(savedUser);
                        }
                        if (typeof window.refreshOsClientCache === 'function') {
                            window.refreshOsClientCache();
                        }
                    }
                } else {
                    showOsFormError(res.message || 'Error');
                }
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Validation error';
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).flat().join(' ');
                }
                showOsFormError(msg);
            },
            complete: function () {
                osAccountXhr = null;
                setSavingState(false);
            }
        });
    }

    var osAccountInitTimer = null;

    window.initOsAccountModal = function () {
        if (osAccountInitTimer) {
            window.clearTimeout(osAccountInitTimer);
        }
        osAccountInitTimer = window.setTimeout(initOsAccountModalNow, 50);
    };

    function initOsAccountModalNow() {
        osAccountInitTimer = null;
        var $form = getForm();
        if (!$form.length) {
            return;
        }

        showOsFormError('');
        resetSaveButton();

        try {
            if ($form.data('bs.validator')) {
                $form.validator('destroy');
            }
        } catch (e) {}

        initOsRegLocation($form);
        $form.find('#phone[data-user-reg-phone]').off('input.osReg change.osReg').on('input.osReg change.osReg', syncOsRegPhone);
    }

    function initOsRegLocation($form) {
        var $box = $form.find('#reg_location_box');
        var $state = $form.find('#reg_state');
        var $township = $form.find('#reg_township');
        if (!$box.length || !$state.length || !$township.length) {
            return;
        }

        var states = [];
        try {
            var raw = $form.find('#reg_mm_location_data').text();
            states = raw ? JSON.parse(raw) : [];
        } catch (err) {
            states = (window.PDS_MYANMAR_NRC_DATA && window.PDS_MYANMAR_NRC_DATA.states) || [];
        }

        var placeholderTownship = $box.data('placeholder-township') || '';
        var placeholderLocked = $box.data('placeholder-township-locked') || placeholderTownship;

        function findState(value) {
            value = String(value || '');
            return states.find(function (state) {
                return state.name_mm === value || state.name_en === value;
            }) || null;
        }

        function townLabel(town) {
            var mm = town.name_mm || '';
            var en = town.name_en || '';
            if (mm && en) {
                return mm + ' (' + en + ')';
            }
            return mm || en;
        }

        function rebuildTownships(selectedTown) {
            var state = findState($state.val());
            var towns = (state && state.townships) ? state.townships : [];
            var current = selectedTown != null ? String(selectedTown) : String($township.val() || '');
            var matched = false;

            $township.empty().append(
                $('<option>', {
                    value: '',
                    text: !$state.val() ? placeholderLocked : placeholderTownship
                })
            );

            towns.forEach(function (town) {
                var value = town.name_mm || town.name_en || '';
                if (!value) {
                    return;
                }
                var isSelected = !!(current && (current === value || current === town.name_en || current === town.name_mm));
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
                $township.append($('<option>', { value: current, text: current, selected: true }));
            }

            $township.prop('disabled', !$state.val());
        }

        $state.off('change.osReg').on('change.osReg', function () {
            rebuildTownships('');
        });
        rebuildTownships($box.data('selected-township') || $township.val() || '');
    }

    $(document).on('click', '#os_account_save_btn', function (e) {
        e.preventDefault();
        e.stopPropagation();
        submitOsAccountForm();
    });

    $(document).on('submit', '#os_account_form', function (e) {
        e.preventDefault();
        submitOsAccountForm();
    });

    $(document).on('click', '#os_account_form .pds-os-password-toggle', function () {
        var $input = $(this).siblings('input');
        if (!$input.length) {
            return;
        }
        var $icon = $(this).find('i');
        var show = $input.attr('type') === 'password';
        $input.attr('type', show ? 'text' : 'password');
        $icon.toggleClass('fa-eye-slash', !show).toggleClass('fa-eye', show);
    });

    $(document).on('shown.bs.modal', '#remoteModelData', function () {
        if (getForm().length) {
            window.initOsAccountModal();
        }
    });

    $(document).on('hidden.bs.modal', '#remoteModelData', function () {
        if (osAccountXhr && osAccountXhr.readyState !== 4) {
            osAccountXhr.abort();
        }
        osAccountXhr = null;
        closeOsDropdowns();
        showOsFormError('');
        resetSaveButton();
    });
})(window, window.jQuery);
