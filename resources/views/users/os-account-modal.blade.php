@php
    $defaultCityId = $defaultCityId ?? auth()->user()->city_id;
@endphp

<div class="modal-dialog modal-xl pds-os-account-modal-wrap" role="document">
    <div class="modal-content pds-os-account-modal">
        <div class="pds-os-account-modal-header">
            <div>
                <h5 class="pds-os-account-modal-title">{{ __('message.user_account_form') }}</h5>
                <p class="pds-os-account-modal-subtitle">{{ __('message.add_form_title', ['form' => __('message.os_name')]) }}</p>
            </div>
            <button type="button" class="pds-os-account-modal-close" data-dismiss="modal" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="os_account_form" method="POST" action="{{ route('users.os-account.store') }}" enctype="multipart/form-data" novalidate data-os-account-form="1"
              data-msg-branch="{{ __('message.select_name', ['select' => __('message.branch_name')]) }}"
              data-msg-os-name="{{ __('message.field_required_msg') }}: {{ __('message.os_name') }}"
              data-msg-login="{{ __('message.field_required_msg') }}: {{ __('message.login_name') }}"
              data-msg-password="{{ __('message.field_required_msg') }}: {{ __('message.password') }}"
              data-msg-phone="{{ __('message.field_required_msg') }}: {{ __('message.phone') }}"
              data-msg-password-length="{{ __('message.passwordInvalid') }}">
            @csrf
            <input type="hidden" name="from_dispatch" value="1">

            <div class="pds-os-account-modal-body">
                <div class="pds-os-section">
                    <div class="pds-os-section-title">{{ __('message.account') }}</div>
                    <div class="pds-dispatch-grid pds-dispatch-grid-3">
                        <div class="pds-dispatch-field">
                            <label for="os_city_id">{{ __('message.branch_name') }}</label>
                            <select name="city_id" id="os_city_id" class="pds-dispatch-input os-select2" required>
                                <option value="">{{ __('message.select_name', ['select' => __('message.branch_name')]) }}</option>
                                @foreach($cities as $city)
                                    <option value="{{ $city->id }}" @selected((string) $defaultCityId === (string) $city->id)>{{ $city->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="pds-dispatch-field">
                            <label for="os_profile_name">{{ __('message.profile_name') }}</label>
                            <input type="text" name="name" id="os_profile_name" class="pds-dispatch-input" required>
                        </div>
                        <div class="pds-dispatch-field">
                            <label for="os_user_type">{{ __('message.user_type') }}</label>
                            <input type="text" id="os_user_type" class="pds-dispatch-input is-readonly" value="OS" readonly>
                            <input type="hidden" name="user_type" value="client">
                        </div>
                    </div>
                </div>

                <div class="pds-os-section">
                    <div class="pds-os-section-title">{{ __('message.login') }}</div>
                    <div class="pds-dispatch-grid pds-dispatch-grid-2">
                        <div class="pds-dispatch-field">
                            <label for="os_username">{{ __('message.login_name') }}</label>
                            <input type="text" name="username" id="os_username" class="pds-dispatch-input" required>
                        </div>
                        <div class="pds-dispatch-field">
                            <label for="os_password">{{ __('message.password') }}</label>
                            <div class="pds-os-password-wrap">
                                <input type="password" name="password" id="os_password" class="pds-dispatch-input" required>
                                <button type="button" class="pds-os-password-toggle" tabindex="-1"><i class="fas fa-eye-slash"></i></button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pds-os-section">
                    @include('partials._myanmar_nrc_box', [
                        'nrcId' => 'os_nrc',
                        'nrcPrefix' => 'os_profile',
                    ])
                </div>

                <div class="pds-os-section">
                    <div class="pds-os-section-title">{{ __('message.contact_details') }}</div>
                    <div class="pds-dispatch-grid pds-dispatch-grid-2">
                        <div class="pds-dispatch-field">
                            <label for="os_account_phone">{{ __('message.phone') }}</label>
                            <input type="text" name="contact_number" id="os_account_phone" class="pds-dispatch-input" required>
                        </div>
                        <div class="pds-dispatch-field">
                            <label for="os_location">{{ __('message.location') }}</label>
                            <input type="text" name="os_profile[location]" id="os_location" class="pds-dispatch-input">
                        </div>
                        <div class="pds-dispatch-field pds-dispatch-grid-span-2">
                            <label for="os_account_address">{{ __('message.address') }}</label>
                            <textarea name="address" id="os_account_address" class="pds-dispatch-input pds-dispatch-textarea" rows="2"></textarea>
                        </div>
                    </div>
                </div>

                <div class="pds-os-section pds-os-section--optional">
                    <div class="pds-os-section-title">
                        {{ __('message.rates') }}
                        <span class="pds-os-section-optional">({{ __('message.optional') }})</span>
                    </div>
                    <div class="pds-dispatch-grid pds-dispatch-grid-4">
                        <div class="pds-dispatch-field">
                            <label for="os_commission">{{ __('message.commission') }}</label>
                            <input type="text" name="os_profile[commission]" id="os_commission" class="pds-dispatch-input" inputmode="decimal">
                        </div>
                        <div class="pds-dispatch-field">
                            <label for="os_cash_back">{{ __('message.cash_back') }}</label>
                            <input type="text" name="os_profile[cash_back]" id="os_cash_back" class="pds-dispatch-input" inputmode="decimal">
                        </div>
                        <div class="pds-dispatch-field">
                            <label for="os_pickup_value">{{ __('message.pickup_value') }}</label>
                            <input type="text" name="os_profile[pickup_value]" id="os_pickup_value" class="pds-dispatch-input" inputmode="decimal">
                        </div>
                        <div class="pds-dispatch-field">
                            <label for="os_assign_value">{{ __('message.assign_value') }}</label>
                            <input type="text" name="os_profile[assign_value]" id="os_assign_value" class="pds-dispatch-input" inputmode="decimal">
                        </div>
                    </div>
                </div>

                <div class="pds-os-section pds-os-section--optional">
                    <div class="pds-os-section-title">
                        {{ __('message.bank') }}
                        <span class="pds-os-section-optional">({{ __('message.optional') }})</span>
                    </div>
                    <div class="pds-dispatch-grid pds-dispatch-grid-3">
                        <div class="pds-dispatch-field">
                            <label for="os_bank_name">{{ __('message.bank_name') }}</label>
                            <input type="text" name="user_bank_account[bank_name]" id="os_bank_name" class="pds-dispatch-input">
                        </div>
                        <div class="pds-dispatch-field">
                            <label for="os_bank_no">{{ __('message.bank_no') }}</label>
                            <input type="text" name="user_bank_account[account_number]" id="os_bank_no" class="pds-dispatch-input">
                        </div>
                        <div class="pds-dispatch-field">
                            <label for="os_remark">{{ __('message.remark_label') }}</label>
                            <input type="text" name="os_profile[remark]" id="os_remark" class="pds-dispatch-input">
                        </div>
                    </div>
                </div>

                <input type="hidden" name="created_by_admin" value="1">
                <input type="hidden" name="is_temp_password" value="1">
            </div>
        </form>

        <div class="pds-os-account-modal-footer">
            <p id="os_account_form_error" class="pds-os-account-form-error" role="alert" hidden></p>
            <div class="pds-os-account-modal-footer-actions">
                <button type="button" class="pds-dispatch-btn pds-dispatch-btn-ghost" data-dismiss="modal">{{ __('message.cancel') }}</button>
                <button type="button" class="pds-dispatch-btn pds-dispatch-btn-primary" id="os_account_save_btn">{{ __('message.save') }}</button>
            </div>
        </div>
    </div>
</div>
