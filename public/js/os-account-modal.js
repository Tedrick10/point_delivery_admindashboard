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
        $('#os_city_id, #os_nrc_region, #os_nrc_town, #os_nrc_type').each(function () {
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

    function validateOsAccountForm($form) {
        var checks = [
            { selector: '#os_city_id', message: $form.attr('data-msg-branch') },
            { selector: '#os_profile_name', message: $form.attr('data-msg-os-name') },
            { selector: '#os_username', message: $form.attr('data-msg-login') },
            { selector: '#os_password', message: $form.attr('data-msg-password') },
            { selector: '#os_account_phone', message: $form.attr('data-msg-phone') }
        ];

        for (var i = 0; i < checks.length; i++) {
            var $field = $form.find(checks[i].selector);
            if (!fieldValue($field)) {
                showOsFormError(checks[i].message);
                scrollToInvalidField($form, $field);
                return false;
            }
        }

        if (fieldValue($form.find('#os_password')).length < 6) {
            var passwordMessage = $form.attr('data-msg-password-length') || 'Password must be at least 6 characters';
            showOsFormError(passwordMessage);
            scrollToInvalidField($form, $form.find('#os_password'));
            return false;
        }

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

        var $modalContent = getModalRoot();
        var dropdownParent = $modalContent.length ? $modalContent : $('#remoteModelData');

        try {
            if (window.PdsMyanmarNrc && $('#os_nrc_box').length) {
                delete window.PdsMyanmarNrc.instances.os_nrc;
                window.PdsMyanmarNrc.init('os_nrc', {
                    dropdownParent: dropdownParent,
                    values: { region: '', town: '', type: '', number: '' }
                });
            }
        } catch (e) {}

        try {
            if ($.fn.select2 && $('#os_city_id').length) {
                if ($('#os_city_id').hasClass('select2-hidden-accessible')) {
                    $('#os_city_id').select2('destroy');
                }
                $('#os_city_id').select2({
                    width: '100%',
                    dropdownParent: dropdownParent,
                    minimumResultsForSearch: Infinity
                });
            }
        } catch (e) {}
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

    $(document).on('click', '.pds-os-password-toggle', function () {
        var $input = $('#os_password');
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
