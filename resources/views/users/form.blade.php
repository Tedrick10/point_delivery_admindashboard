<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter pds-user-reg-page">
        <?php $id = $id ?? null; ?>
        @if(isset($id))
            {!! html()->modelForm($data, 'PATCH', route('users.update', $id))->id('user_form')->attribute('enctype', 'multipart/form-data')->open() !!}
        @else
            {!! html()->form('POST', route('users.store'))->id('user_form')->attribute('enctype', 'multipart/form-data')->open() !!}
        @endif

        <div class="card pds-page-card pds-user-reg-card">
            <div class="card-header pds-page-header pds-user-reg-header">
                <div class="pds-user-reg-header__copy">
                    <div class="pds-user-reg-header__eyebrow">
                        <i class="fas fa-store" aria-hidden="true"></i>
                        <span>{{ __('message.online_shop') }}</span>
                    </div>
                    <h4 class="card-title pds-page-title pds-user-reg-header__title mb-0">{{ $pageTitle }}</h4>
                    @unless(isset($id))
                        <p class="pds-user-reg-subtitle mb-0">{{ __('message.sign_up_account') }}</p>
                    @endunless
                </div>
                <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-primary pds-user-reg-back">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i>
                    <span>{{ __('message.back') }}</span>
                </a>
            </div>

            <div class="card-body pds-page-body pds-user-reg-body">
                @if(isset($errors) && $errors->any())
                    <div class="alert alert-danger pds-user-reg-alert" role="alert">
                        <strong>{{ $errors->first() }}</strong>
                    </div>
                @endif
                <div class="pds-user-reg-layout">
                    <aside class="pds-user-reg-aside">
                        @include('partials._profile_upload', [
                            'profileImage' => $profileImage ?? null,
                            'profileTitle' => __('message.profile'),
                        ])
                    </aside>

                    <div class="pds-user-reg-main">
                        @include('users.partials._registration_fields', ['data' => $data ?? null, 'id' => $id ?? null])

                        @if(!isset($id))
                            <input type="hidden" name="created_by_admin" value="1">
                            <input type="hidden" name="is_temp_password" value="1">
                        @endif

                        <div class="pds-user-reg-footer">
                            {!! html()->submit(isset($id) ? __('message.update') : __('message.save'))->class('btn btn-primary pds-user-reg-save') !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {!! html()->form()->close() !!}
    </div>
    @push('bottom_script')
    <script>
        $(document).ready(function() {
            $('.pds-os-password-toggle').on('click', function() {
                var input = $(this).siblings('input');
                var icon = $(this).find('i');
                if (input.attr('type') === 'password') {
                    input.attr('type', 'text');
                    icon.removeClass('fa-eye-slash').addClass('fa-eye');
                } else {
                    input.attr('type', 'password');
                    icon.removeClass('fa-eye').addClass('fa-eye-slash');
                }
            });

            var formRules = {
                name: { required: true },
                username: { required: true, minlength: 3 },
                'os_profile[address_unit]': { required: true },
                'os_profile[state_division]': { required: true },
                'os_profile[township]': { required: true },
                'os_profile[kpay_name]': { required: true },
                'os_profile[kpay_no]': { required: true },
            };

            @if(!isset($id))
            formRules.phone = { required: true };
            formRules.password = { required: true, minlength: 6 };
            formRules.password_confirmation = { required: true, equalTo: '#reg_password' };
            @else
            formRules.password = {
                minlength: {
                    param: 6,
                    depends: function () {
                        return $('#reg_password').val().length > 0;
                    }
                },
                required: {
                    depends: function () {
                        return $('#reg_password_confirmation').val().length > 0;
                    }
                }
            };
            formRules.password_confirmation = {
                equalTo: '#reg_password',
                required: {
                    depends: function () {
                        return $('#reg_password').val().length > 0;
                    }
                }
            };
            @endif

            $("#user_form").validate({
                ignore: [],
                rules: formRules,
                messages: {
                    name: { required: "{{ __('message.please_enter_name') }}" },
                    username: { required: "{{ __('message.please_enter_username') }}" },
                    phone: { required: "{{ __('message.please_enter_contact_number') }}" },
                    password: {
                        required: "{{ __('message.please_enter_password') }}",
                        minlength: "{{ __('message.please_enter_new_password') }}"
                    },
                    password_confirmation: {
                        required: "{{ __('message.please_enter_confirm_password') }}",
                        equalTo: "{{ __('message.password_does_not_match') }}"
                    },
                },
                errorClass: "help-block error",
                highlight: function(element) {
                    $(element).closest(".form-group.row").addClass("has-error");
                },
                unhighlight: function(element) {
                    $(element).closest(".form-group.row").removeClass("has-error");
                },
                errorPlacement: function(error, element) {
                    if (element.attr('id') === 'phone') {
                        error.appendTo(element.closest('.pds-dispatch-field'));
                        return;
                    }
                    if (element.hasClass('select2js')) {
                        error.insertAfter(element.next('.select2-container'));
                    } else {
                        error.insertAfter(element);
                    }
                },
                submitHandler: function (form) {
                    if (typeof window.syncUserRegPhoneField === 'function') {
                        window.syncUserRegPhoneField();
                    }
                    // Avoid jquery-validate re-entry; native submit after phone sync.
                    form.submit();
                }
            });

            @if(isset($id))
            $('#user_form [type="submit"]').prop('disabled', false).removeClass('disabled');
            @endif

            function toE164Mm(value) {
                var digits = String(value || '').replace(/\D/g, '');
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
                if (!digits) {
                    return '';
                }
                return digits.indexOf('95') === 0 ? '+' + digits : '+95' + digits;
            }

            function isPlausiblyMmMobile(value) {
                var digits = String(value || '').replace(/\D/g, '');
                while (digits.indexOf('95') === 0 && digits.length > 10) {
                    digits = digits.slice(2);
                }
                digits = digits.replace(/^0+/, '');
                return /^9\d{7,9}$/.test(digits);
            }

            window.syncUserRegPhoneField = function () {
                var input = document.querySelector('#phone[data-user-reg-phone]');
                if (!input || input.hasAttribute('readonly')) {
                    return true;
                }

                var iti = window.userRegPhoneIti;
                var e164 = '';
                try {
                    if (iti && typeof iti.getNumber === 'function') {
                        e164 = iti.getNumber() || '';
                    }
                } catch (err) {
                    e164 = '';
                }
                if (!e164) {
                    e164 = toE164Mm(input.value);
                }

                if (isPlausiblyMmMobile(e164) || isPlausiblyMmMobile(input.value)) {
                    $('#contact_number_hidden').val(e164 || toE164Mm(input.value));
                    return true;
                }

                return false;
            };

            function initUserRegPhone() {
                var input = document.querySelector('#phone[data-user-reg-phone]');
                if (!input) {
                    return;
                }
                if (!window.intlTelInput) {
                    window.setTimeout(initUserRegPhone, 120);
                    return;
                }
                if (input.classList.contains('iti__tel-input') || input.closest('.iti')) {
                    return;
                }

                var isReadonly = input.hasAttribute('readonly');
                var iti = window.intlTelInput(input, {
                    initialCountry: 'mm',
                    onlyCountries: ['mm'],
                    separateDialCode: true,
                    allowDropdown: false,
                    nationalMode: true,
                    autoPlaceholder: 'aggressive',
                    utilsScript: "{{ asset('vendor/intlTelInput/js/utils.js') }}",
                    hiddenInput: null
                });

                window.userRegPhoneIti = iti;

                function applyStoredNumber() {
                    if (!isReadonly) {
                        if (input.value) {
                            iti.setNumber(toE164Mm(input.value));
                        }
                        return;
                    }
                    var stored = $('#contact_number_hidden').val() || input.value;
                    if (!stored) {
                        return;
                    }
                    iti.setNumber(toE164Mm(stored));
                }

                window.setTimeout(applyStoredNumber, 0);
                window.setTimeout(applyStoredNumber, 250);

                if (isReadonly) {
                    return;
                }

                $(input).on('input change countrychange', function () {
                    $(input).closest('.pds-dispatch-field').find('.pds-phone-error').remove();
                    window.syncUserRegPhoneField();
                });
            }

            initUserRegPhone();

            function initUserRegLocation() {
                var $box = $('#reg_location_box');
                var $state = $('#reg_state');
                var $township = $('#reg_township');
                if (!$box.length || !$state.length || !$township.length) {
                    return;
                }

                var states = [];
                try {
                    var raw = $('#reg_mm_location_data').text();
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

                function rebuildTownships(selectedTown, keepDisabledEmpty) {
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
                        $township.append($('<option>', {
                            value: current,
                            text: current,
                            selected: true
                        }));
                    }

                    $township.prop('disabled', !!(keepDisabledEmpty && !$state.val()));
                }

                $state.on('change', function () {
                    rebuildTownships('', true);
                });

                // Ensure township options match the selected region on first paint / edit.
                rebuildTownships($box.data('selected-township') || $township.val() || '', true);
            }

            initUserRegLocation();
        });
    </script>
    @endpush
</x-master-layout>
