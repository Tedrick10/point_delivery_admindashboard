@php
    $profile = old('os_profile', optional($data ?? null)->os_profile ?? []);
    if (is_string($profile)) {
        $profile = json_decode($profile, true) ?: [];
    }
    $isEdit = isset($id);
    $nrcParts = [];
    if (!empty($profile['nrc'])) {
        if (preg_match('/^(.+?)\/(.+?)\((.+?)\)(.+)$/', $profile['nrc'], $m)) {
            $nrcParts = ['region' => $m[1], 'town' => $m[2], 'type' => $m[3], 'number' => $m[4]];
        }
    }

    $phoneValue = old('contact_number', optional($data ?? null)->contact_number ?? '');
    $phoneNational = preg_replace('/^\+?95\s*/', '', trim((string) $phoneValue));
    $phoneStored = trim((string) (optional($data ?? null)->contact_number ?? $phoneValue));
@endphp

<div class="pds-user-reg-form">
    <div class="pds-user-reg-section">
        <h6 class="pds-user-reg-section__title">
            <i class="fas fa-user"></i>
            <span>{{ __('message.reg_section_personal') }}</span>
        </h6>
        <div class="pds-dispatch-grid pds-dispatch-grid-2">
            <div class="pds-dispatch-field pds-dispatch-field--full">
                <label for="reg_name">{{ __('message.reg_name_business') }} <span class="text-danger">*</span></label>
                <input type="text" name="name" id="reg_name" class="pds-dispatch-input"
                       placeholder="{{ __('message.reg_placeholder_name') }}"
                       value="{{ old('name', optional($data ?? null)->name) }}" required>
            </div>

            <div class="pds-dispatch-field">
                <label for="reg_dob">{{ __('message.date_of_birth') }} <span class="text-danger">*</span></label>
                <input type="date" name="os_profile[date_of_birth]" id="reg_dob" class="pds-dispatch-input"
                       placeholder="{{ __('message.reg_placeholder_dob') }}"
                       value="{{ old('os_profile.date_of_birth', $profile['date_of_birth'] ?? '') }}" required>
            </div>
        </div>
    </div>

    <div class="pds-user-reg-section">
        <h6 class="pds-user-reg-section__title">
            <i class="fas fa-map-marker-alt"></i>
            <span>{{ __('message.reg_section_address') }}</span>
        </h6>
        <div class="pds-dispatch-grid pds-dispatch-grid-2">
            <div class="pds-dispatch-field">
                <label for="reg_address_unit">{{ __('message.address_unit') }} <span class="text-danger">*</span></label>
                <input type="text" name="os_profile[address_unit]" id="reg_address_unit" class="pds-dispatch-input"
                       placeholder="{{ __('message.reg_placeholder_address_unit') }}"
                       value="{{ old('os_profile.address_unit', $profile['address_unit'] ?? '') }}" required>
            </div>

            <div class="pds-dispatch-field">
                <label for="reg_state">{{ __('message.state_division') }} <span class="text-danger">*</span></label>
                <input type="text" name="os_profile[state_division]" id="reg_state" class="pds-dispatch-input"
                       placeholder="{{ __('message.reg_placeholder_state_division') }}"
                       value="{{ old('os_profile.state_division', $profile['state_division'] ?? '') }}" required>
            </div>

            <div class="pds-dispatch-field">
                <label for="reg_township">{{ __('message.township_city') }} <span class="text-danger">*</span></label>
                <input type="text" name="os_profile[township]" id="reg_township" class="pds-dispatch-input"
                       placeholder="{{ __('message.reg_placeholder_township') }}"
                       value="{{ old('os_profile.township', $profile['township'] ?? '') }}" required>
            </div>

            <div class="pds-dispatch-field">
                <label for="reg_street">{{ __('message.street_name') }} <span class="text-danger">*</span></label>
                <input type="text" name="os_profile[street]" id="reg_street" class="pds-dispatch-input"
                       placeholder="{{ __('message.reg_placeholder_street') }}"
                       value="{{ old('os_profile.street', $profile['street'] ?? '') }}" required>
            </div>
        </div>
    </div>

    <div class="pds-user-reg-section pds-user-reg-section--nrc">
        <h6 class="pds-user-reg-section__title">
            <i class="fas fa-id-card"></i>
            <span>{{ __('message.registration_nrc') }}</span>
        </h6>
        @include('partials._myanmar_nrc_box', [
            'nrcId' => 'user_reg_nrc',
            'nrcPrefix' => 'os_profile',
            'nrcRegion' => old('os_profile.nrc_region', $nrcParts['region'] ?? ''),
            'nrcTown' => old('os_profile.nrc_town', $nrcParts['town'] ?? ''),
            'nrcType' => old('os_profile.nrc_type', $nrcParts['type'] ?? ''),
            'nrcNumber' => old('os_profile.nrc_number', $nrcParts['number'] ?? ''),
            'nrcPreview' => $profile['nrc'] ?? '',
        ])
    </div>

    <div class="pds-user-reg-section">
        <h6 class="pds-user-reg-section__title">
            <i class="fas fa-wallet"></i>
            <span>{{ __('message.kpay_name') }} / {{ __('message.kpay_no') }}</span>
        </h6>
        <div class="pds-dispatch-grid pds-dispatch-grid-2">
            <div class="pds-dispatch-field">
                <label for="reg_kpay_name">{{ __('message.kpay_name') }} <span class="text-danger">*</span></label>
                <input type="text" name="os_profile[kpay_name]" id="reg_kpay_name" class="pds-dispatch-input"
                       value="{{ old('os_profile.kpay_name', $profile['kpay_name'] ?? '') }}" required>
            </div>
            <div class="pds-dispatch-field">
                <label for="reg_kpay_no">{{ __('message.kpay_no') }} <span class="text-danger">*</span></label>
                <input type="text" name="os_profile[kpay_no]" id="reg_kpay_no" class="pds-dispatch-input"
                       value="{{ old('os_profile.kpay_no', $profile['kpay_no'] ?? '') }}" required>
            </div>
        </div>
    </div>

    <div class="pds-user-reg-section">
        <h6 class="pds-user-reg-section__title">
            <i class="fas fa-lock"></i>
            <span>{{ __('message.reg_section_account') }}</span>
        </h6>
        <div class="pds-dispatch-grid pds-dispatch-grid-2">
            <div class="pds-dispatch-field">
                <label for="phone">{{ __('message.login_mobile') }} <span class="text-danger">*</span></label>
                <div class="pds-phone-field {{ $isEdit ? 'pds-phone-field--readonly' : '' }}">
                    <input type="tel"
                           id="phone"
                           class="pds-dispatch-input"
                           data-user-reg-phone="1"
                           placeholder="{{ __('message.reg_placeholder_phone') }}"
                           value="{{ $phoneNational }}"
                           @if($isEdit) readonly tabindex="-1" @else required @endif>
                </div>
                @if($isEdit)
                    <input type="hidden" name="contact_number" id="contact_number_hidden" value="{{ $phoneStored }}">
                @endif
                <small class="pds-field-hint">{{ __('message.phone_country_hint') }}</small>
            </div>

            @if(!$isEdit)
            <div class="pds-dispatch-field">
                <label for="reg_password">{{ __('message.password') }} <span class="text-danger">*</span></label>
                <div class="pds-os-password-wrap">
                    <input type="password" name="password" id="reg_password" class="pds-dispatch-input"
                           placeholder="{{ __('message.reg_placeholder_password') }}" required>
                    <button type="button" class="pds-os-password-toggle" tabindex="-1"><i class="fas fa-eye-slash"></i></button>
                </div>
            </div>

            <div class="pds-dispatch-field">
                <label for="reg_password_confirmation">{{ __('message.confirm_password') }} <span class="text-danger">*</span></label>
                <div class="pds-os-password-wrap">
                    <input type="password" name="password_confirmation" id="reg_password_confirmation" class="pds-dispatch-input"
                           placeholder="{{ __('message.reg_placeholder_password_confirm') }}" required>
                    <button type="button" class="pds-os-password-toggle" tabindex="-1"><i class="fas fa-eye-slash"></i></button>
                </div>
            </div>
            @else
            <div class="pds-dispatch-field pds-dispatch-field--full">
                <div class="pds-user-reg-password-change">
                    <h6 class="pds-user-reg-password-change__title">{{ __('message.change_password') }}</h6>
                    <p class="pds-user-reg-password-change__hint">{{ __('message.reg_change_password_hint') }}</p>
                </div>
            </div>

            <div class="pds-dispatch-field">
                <label for="reg_password">{{ __('message.new_password') }}</label>
                <div class="pds-os-password-wrap">
                    <input type="password" name="password" id="reg_password" class="pds-dispatch-input"
                           placeholder="{{ __('message.reg_placeholder_new_password') }}" autocomplete="new-password">
                    <button type="button" class="pds-os-password-toggle" tabindex="-1"><i class="fas fa-eye-slash"></i></button>
                </div>
            </div>

            <div class="pds-dispatch-field">
                <label for="reg_password_confirmation">{{ __('message.confirm_new_password') }}</label>
                <div class="pds-os-password-wrap">
                    <input type="password" name="password_confirmation" id="reg_password_confirmation" class="pds-dispatch-input"
                           placeholder="{{ __('message.reg_placeholder_password_confirm') }}" autocomplete="new-password">
                    <button type="button" class="pds-os-password-toggle" tabindex="-1"><i class="fas fa-eye-slash"></i></button>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
