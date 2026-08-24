<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter">
    <div>
        <?php $id = $id ?? null; ?>
        @if(isset($id))
            {!! html()->modelForm($data, 'PATCH', route('users.update', $id))->id('user_form')->attribute('enctype', 'multipart/form-data')->open() !!}
        @else
            {!! html()->form('POST', route('users.store'))->id('user_form')->attribute('enctype', 'multipart/form-data')->open() !!}
        @endif
        <div class="row">
            <div class="col-lg-12">
                <div class="card pds-page-card pds-user-reg-card">
                    <div class="card-header pds-page-header d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="card-title pds-page-title mb-0">{{ $pageTitle }}</h4>
                            <p class="pds-user-reg-subtitle mb-0">{{ __('message.sign_up_account') }}</p>
                        </div>
                        <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-primary">{{ __('message.back') }}</a>
                    </div>

                    <div class="card-body pds-page-body pds-user-reg-body">
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
            </div>
        </div>
        {!! html()->form()->close() !!}
    </div>
    </div>
    @section('bottom_script')
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
            formRules.contact_number = { required: true };
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

            formValidation("#user_form", formRules, {
                name: { required: "{{ __('message.please_enter_name') }}" },
                username: { required: "{{ __('message.please_enter_username') }}" },
                contact_number: { required: "{{ __('message.please_enter_contact_number') }}" },
                password: {
                    required: "{{ __('message.please_enter_password') }}",
                    minlength: "{{ __('message.please_enter_new_password') }}"
                },
                password_confirmation: {
                    required: "{{ __('message.please_enter_confirm_password') }}",
                    equalTo: "{{ __('message.password_does_not_match') }}"
                },
            });

            @if(isset($id))
            $('#user_form [type="submit"]').prop('disabled', false).removeClass('disabled');
            @endif

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
                    hiddenInput: isReadonly ? null : 'contact_number'
                });

                window.userRegPhoneIti = iti;

                function applyStoredNumber() {
                    if (!isReadonly) {
                        if (input.value) {
                            iti.setNumber('+95' + String(input.value).replace(/\D/g, ''));
                        }
                        return;
                    }
                    var stored = $('#contact_number_hidden').val() || input.value;
                    if (!stored) {
                        return;
                    }
                    var normalized = String(stored).trim();
                    if (normalized.indexOf('+') !== 0) {
                        normalized = '+95' + normalized.replace(/\D/g, '');
                    }
                    iti.setNumber(normalized);
                }

                window.setTimeout(applyStoredNumber, 0);
                window.setTimeout(applyStoredNumber, 250);

                if (isReadonly) {
                    return;
                }

                $('#user_form').on('submit.userRegPhone', function (e) {
                    if (!iti.isValidNumber()) {
                        e.preventDefault();
                        var $field = $(input).closest('.pds-dispatch-field');
                        $field.find('.pds-phone-error').remove();
                        $field.append('<span class="help-block error pds-phone-error">{{ __('message.please_enter_contact_number') }}</span>');
                    }
                });

                $(input).on('input change countrychange', function () {
                    $(input).closest('.pds-dispatch-field').find('.pds-phone-error').remove();
                });
            }

            initUserRegPhone();
        });
    </script>
    @endsection
</x-master-layout>
