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

    $selectedState = trim((string) old('os_profile.state_division', $profile['state_division'] ?? ''));
    $selectedTownship = trim((string) old('os_profile.township', $profile['township'] ?? ''));
    $myanmarNrcData = json_decode(@file_get_contents(public_path('data/myanmar-nrc.json')) ?: '[]', true) ?: [];
    $mmStates = $myanmarNrcData['states'] ?? [];
    $selectedStateTownships = [];
    foreach ($mmStates as $state) {
        $stateNameMm = (string) ($state['name_mm'] ?? '');
        $stateNameEn = (string) ($state['name_en'] ?? '');
        if ($selectedState !== '' && ($selectedState === $stateNameMm || $selectedState === $stateNameEn)) {
            $selectedStateTownships = $state['townships'] ?? [];
            // Normalize stored edit/old value to name_mm for the select value.
            $selectedState = $stateNameMm !== '' ? $stateNameMm : $selectedState;
            break;
        }
    }
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
                <label for="reg_name">{{ __('message.reg_os_name') }} <span class="text-danger">*</span></label>
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
                           @if($isEdit)
                               readonly tabindex="-1"
                           @else
                               name="phone"
                               required
                           @endif>
                </div>
                {{-- Edit posts stored E.164; create prefers hidden when JS syncs, else falls back to name=phone. --}}
                <input type="hidden"
                       name="contact_number"
                       id="contact_number_hidden"
                       value="{{ $phoneStored }}"
                       @unless($isEdit) data-os-create-phone="1" @endunless>
                <small class="pds-field-hint">{{ __('message.phone_country_hint') }}</small>
                @error('contact_number')
                    <span class="help-block error">{{ $message }}</span>
                @enderror
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
        <div class="pds-dispatch-grid pds-dispatch-grid-2"
             id="reg_location_box"
             data-selected-state="{{ $selectedState }}"
             data-selected-township="{{ $selectedTownship }}"
             data-placeholder-state="{{ __('message.reg_placeholder_state_division') }}"
             data-placeholder-township="{{ __('message.reg_placeholder_township') }}"
             data-placeholder-township-locked="{{ __('message.nrc_placeholder_town_locked') }}">
            <div class="pds-dispatch-field">
                <label for="reg_state">{{ __('message.region') }} <span class="text-danger">*</span></label>
                <select name="os_profile[state_division]" id="reg_state" class="pds-dispatch-input pds-dispatch-select" required>
                    <option value="">{{ __('message.reg_placeholder_state_division') }}</option>
                    @foreach($mmStates as $state)
                        @php
                            $stateValue = (string) ($state['name_mm'] ?? $state['name_en'] ?? '');
                            $stateLabel = trim(($state['name_mm'] ?? '').(($state['name_en'] ?? '') !== '' ? ' ('.$state['name_en'].')' : ''));
                        @endphp
                        <option value="{{ $stateValue }}"
                                data-name-en="{{ $state['name_en'] ?? '' }}"
                                @selected($selectedState === $stateValue || $selectedState === ($state['name_en'] ?? ''))>
                            {{ $stateLabel !== '' ? $stateLabel : $stateValue }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="pds-dispatch-field">
                <label for="reg_township">{{ __('message.township') }} <span class="text-danger">*</span></label>
                <select name="os_profile[township]" id="reg_township" class="pds-dispatch-input pds-dispatch-select" required @disabled($selectedState === '')>
                    <option value="">
                        {{ $selectedState === '' ? __('message.nrc_placeholder_town_locked') : __('message.reg_placeholder_township') }}
                    </option>
                    @foreach($selectedStateTownships as $town)
                        @php
                            $townValue = (string) ($town['name_mm'] ?? $town['name_en'] ?? '');
                            $townLabel = trim(($town['name_mm'] ?? '').(($town['name_en'] ?? '') !== '' ? ' ('.$town['name_en'].')' : ''));
                            $townMatches = $selectedTownship !== '' && (
                                $selectedTownship === $townValue
                                || $selectedTownship === ($town['name_en'] ?? '')
                                || $selectedTownship === ($town['name_mm'] ?? '')
                            );
                            if ($townMatches) {
                                $selectedTownship = $townValue;
                            }
                        @endphp
                        <option value="{{ $townValue }}" @selected($townMatches)>
                            {{ $townLabel !== '' ? $townLabel : $townValue }}
                        </option>
                    @endforeach
                    @if($selectedTownship !== '' && collect($selectedStateTownships)->every(function ($town) use ($selectedTownship) {
                        return $selectedTownship !== ($town['name_mm'] ?? '')
                            && $selectedTownship !== ($town['name_en'] ?? '');
                    }))
                        <option value="{{ $selectedTownship }}" selected>{{ $selectedTownship }}</option>
                    @endif
                </select>
            </div>
        </div>
        <script type="application/json" id="reg_mm_location_data">@json($mmStates)</script>
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

    <div class="pds-user-reg-section">
        <h6 class="pds-user-reg-section__title">
            <i class="fas fa-clipboard-check"></i>
            <span>{{ __('message.status') }}</span>
        </h6>
        <div class="pds-dispatch-grid pds-dispatch-grid-2">
            <div class="pds-dispatch-field pds-dispatch-field--full">
                <label for="reg_approval_status">{{ __('message.status') }} <span class="text-danger">*</span></label>
                @php
                    $approvalValue = old(
                        'approval_status',
                        optional($data ?? null)->approval_status ?? \App\Models\User::APPROVAL_APPROVED
                    );
                @endphp
                <select name="approval_status" id="reg_approval_status" class="pds-dispatch-input pds-dispatch-select" required>
                    <option value="pending" @selected($approvalValue === 'pending')>{{ __('message.pending') }}</option>
                    <option value="approved" @selected($approvalValue === 'approved')>{{ __('message.approved') }}</option>
                    <option value="rejected" @selected($approvalValue === 'rejected')>{{ __('message.rejected') }}</option>
                </select>
            </div>
        </div>
    </div>
</div>
