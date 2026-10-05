<div class="modal-dialog modal-xl pds-os-account-modal-wrap" role="document">
    <div class="modal-content pds-os-account-modal pds-os-account-modal--reg">
        <div class="pds-os-account-modal-header">
            <div>
                <h5 class="pds-os-account-modal-title">{{ __('message.add_form_title', ['form' => __('message.online_shop')]) }}</h5>
                <p class="pds-os-account-modal-subtitle">{{ __('message.sign_up_account') }}</p>
            </div>
            <button type="button" class="pds-os-account-modal-close" data-dismiss="modal" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="os_account_form" method="POST" action="{{ route('users.os-account.store') }}" enctype="multipart/form-data" novalidate data-os-account-form="1"
              data-msg-os-name="{{ __('message.please_enter_name') }}"
              data-msg-login="{{ __('message.please_enter_username') }}"
              data-msg-password="{{ __('message.please_enter_password') }}"
              data-msg-password-confirm="{{ __('message.please_enter_confirm_password') }}"
              data-msg-password-match="{{ __('message.password_does_not_match') }}"
              data-msg-phone="{{ __('message.please_enter_contact_number') }}"
              data-msg-address="{{ __('message.field_required_msg') }}: {{ __('message.address') }}"
              data-msg-state="{{ __('message.field_required_msg') }}: {{ __('message.region') }}"
              data-msg-township="{{ __('message.field_required_msg') }}: {{ __('message.township') }}"
              data-msg-kpay-name="{{ __('message.field_required_msg') }}: {{ __('message.reg_kbz_pay_name') }}"
              data-msg-kpay-no="{{ __('message.field_required_msg') }}: {{ __('message.reg_kbz_pay_number') }}"
              data-msg-password-length="{{ __('message.passwordInvalid') }}">
            @csrf
            <input type="hidden" name="from_dispatch" value="1">
            <input type="hidden" name="user_type" value="client">
            <input type="hidden" name="created_by_admin" value="1">
            <input type="hidden" name="is_temp_password" value="1">
            <input type="hidden" name="approval_status" value="approved">

            <div class="pds-os-account-modal-body pds-user-reg-body">
                <div class="pds-user-reg-layout">
                    <aside class="pds-user-reg-aside">
                        @include('partials._profile_upload', [
                            'previewId' => 'os_account_profile_preview',
                            'profileTitle' => __('message.profile'),
                        ])
                    </aside>
                    <div class="pds-user-reg-main">
                        @include('users.partials._registration_fields', [
                            'data' => null,
                            'id' => null,
                            'hideApprovalStatus' => true,
                        ])
                    </div>
                </div>
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
