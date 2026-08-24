@php
    $profile = old('os_profile', optional($data ?? null)->os_profile ?? []);
    if (is_string($profile)) {
        $profile = json_decode($profile, true) ?: [];
    }
    $isEdit = isset($id);

    $phoneValue = old('contact_number', optional($data ?? null)->contact_number ?? '');
    $phoneNational = preg_replace('/^\+?95\s*/', '', trim((string) $phoneValue));
    $phoneStored = trim((string) (optional($data ?? null)->contact_number ?? $phoneValue));
    $usernameValue = old('username', optional($data ?? null)->username ?? '');
@endphp

<div class="pds-user-reg-form">
    <div class="pds-user-reg-section">
        <h6 class="pds-user-reg-section__title">
            <i class="fas fa-lock"></i>
            <span>{{ __('message.reg_section_account') }}</span>
        </h6>
        <div class="pds-dispatch-grid pds-dispatch-grid-2">
            <div class="pds-dispatch-field pds-dispatch-field--full">
                <label for="reg_username">{{ __('message.username') }} <span class="text-danger">*</span></label>
                <input type="text" name="username" id="reg_username" class="pds-dispatch-input"
                       placeholder="{{ __('message.reg_placeholder_username') }}"
                       value="{{ $usernameValue }}"
                       autocomplete="username"
                       @if($isEdit) readonly tabindex="-1" @else required @endif>
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

    <div class="pds-user-reg-section">
        <h6 class="pds-user-reg-section__title">
            <i class="fas fa-user"></i>
            <span>{{ __('message.reg_section_personal') }}</span>
        </h6>
        <div class="pds-dispatch-grid pds-dispatch-grid-2">
            <div class="pds-dispatch-field">
                <label for="reg_name">{{ __('message.os_name') }} <span class="text-danger">*</span></label>
                <input type="text" name="name" id="reg_name" class="pds-dispatch-input"
                       placeholder="{{ __('message.reg_placeholder_os_name') }}"
                       value="{{ old('name', optional($data ?? null)->name) }}" required>
            </div>

            <div class="pds-dispatch-field">
                <label for="phone">{{ __('message.phone') }} <span class="text-danger">*</span></label>
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
        </div>
    </div>

    <div class="pds-user-reg-section">
        <h6 class="pds-user-reg-section__title">
            <i class="fas fa-map-marker-alt"></i>
            <span>{{ __('message.reg_section_address') }}</span>
        </h6>
        <div class="pds-dispatch-grid pds-dispatch-grid-2">
            <div class="pds-dispatch-field pds-dispatch-field--full">
                <label for="reg_address_unit">{{ __('message.address') }} <span class="text-danger">*</span></label>
                <input type="text" name="os_profile[address_unit]" id="reg_address_unit" class="pds-dispatch-input"
                       placeholder="{{ __('message.reg_placeholder_address_unit') }}"
                       value="{{ old('os_profile.address_unit', $profile['address_unit'] ?? '') }}" required>
            </div>
        </div>
    </div>

    <div class="pds-user-reg-section">
        <h6 class="pds-user-reg-section__title">
            <i class="fas fa-map-marker-alt"></i>
            <span>{{ __('message.region') }} / {{ __('message.township') }}</span>
        </h6>
        <div class="pds-dispatch-grid pds-dispatch-grid-2">
            <div class="pds-dispatch-field">
                <label for="reg_state">{{ __('message.region') }} <span class="text-danger">*</span></label>
                <input type="text" name="os_profile[state_division]" id="reg_state" class="pds-dispatch-input"
                       placeholder="{{ __('message.reg_placeholder_state_division') }}"
                       value="{{ old('os_profile.state_division', $profile['state_division'] ?? '') }}" required>
            </div>

            <div class="pds-dispatch-field">
                <label for="reg_township">{{ __('message.township') }} <span class="text-danger">*</span></label>
                <input type="text" name="os_profile[township]" id="reg_township" class="pds-dispatch-input"
                       placeholder="{{ __('message.reg_placeholder_township') }}"
                       value="{{ old('os_profile.township', $profile['township'] ?? '') }}" required>
            </div>
        </div>
    </div>

    <div class="pds-user-reg-section">
        <h6 class="pds-user-reg-section__title">
            <i class="fas fa-wallet"></i>
            <span>{{ __('message.reg_kbz_pay_name') }} / {{ __('message.reg_kbz_pay_number') }}</span>
        </h6>
        <div class="pds-dispatch-grid pds-dispatch-grid-2">
            <div class="pds-dispatch-field">
                <label for="reg_kpay_name">{{ __('message.reg_kbz_pay_name') }} <span class="text-danger">*</span></label>
                <input type="text" name="os_profile[kpay_name]" id="reg_kpay_name" class="pds-dispatch-input"
                       placeholder="{{ __('message.reg_placeholder_kbz_pay_name') }}"
                       value="{{ old('os_profile.kpay_name', $profile['kpay_name'] ?? '') }}" required>
            </div>
            <div class="pds-dispatch-field">
                <label for="reg_kpay_no">{{ __('message.reg_kbz_pay_number') }} <span class="text-danger">*</span></label>
                <input type="text" name="os_profile[kpay_no]" id="reg_kpay_no" class="pds-dispatch-input"
                       placeholder="{{ __('message.reg_placeholder_kbz_pay_number') }}"
                       value="{{ old('os_profile.kpay_no', $profile['kpay_no'] ?? '') }}" required>
            </div>
        </div>
    </div>
</div>
