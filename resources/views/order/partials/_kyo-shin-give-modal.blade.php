<style>
    .pds-assign-action-btn--kyo-shin { background: #fff7ed !important; border: 1px solid #fdba74; box-shadow: none; color: #c2410c; }
    .pds-assign-action-btn--kyo-shin:disabled { opacity: .45; }
    body.pds-admin .pds-assign-action-btn--kyo-shin,
    body.pds-admin .pds-assign-action-btn--kyo-shin .pds-assign-action-btn__icon,
    body.pds-admin .pds-assign-action-btn--kyo-shin .pds-assign-action-btn__label { color: #c2410c !important; }
    body.pds-admin .pds-assign-action-btn--kyo-shin .pds-assign-action-btn__icon { background: #ffedd5; }
    .pds-kyo-shin-row-badge {
        display: inline-flex; align-items: center; margin-left: 6px;
        padding: 2px 7px; border-radius: 999px; font-size: 10px; font-weight: 800;
        background: #ffedd5; color: #c2410c; letter-spacing: 0;
    }
    .pds-kyo-shin-modal {
        border: 0;
        border-radius: 22px;
        overflow: hidden;
        box-shadow: 0 24px 60px rgba(28, 25, 23, .18);
        letter-spacing: 0;
        word-spacing: normal;
    }
    .pds-kyo-shin-modal__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        padding: 22px 22px 16px;
        background: linear-gradient(180deg, #fff7ed 0%, #ffffff 100%);
        border-bottom: 1px solid #ffedd5;
    }
    .pds-kyo-shin-modal__heading { display: flex; align-items: flex-start; gap: 12px; min-width: 0; }
    .pds-kyo-shin-modal__icon {
        width: 44px; height: 44px; border-radius: 14px; display: grid; place-items: center;
        background: linear-gradient(135deg, #FE6F07, #ff8f3d); color: #fff;
        box-shadow: 0 10px 20px rgba(254, 111, 7, .28); flex-shrink: 0;
    }
    .pds-kyo-shin-modal__title { margin: 0; font-size: 1.15rem; font-weight: 800; color: #1c1917; line-height: 1.35; letter-spacing: 0; word-spacing: normal; }
    .pds-kyo-shin-modal__count {
        display: inline-flex; margin-top: 6px; padding: 3px 10px; border-radius: 999px;
        background: #ffedd5; color: #c2410c !important; font-size: 12px; font-weight: 700;
        letter-spacing: 0; word-spacing: normal;
    }
    .pds-kyo-shin-modal__close {
        width: 36px; height: 36px; border: 0; border-radius: 10px; background: #fff; color: #78716c;
        box-shadow: inset 0 0 0 1px #e7e5e4;
    }
    .pds-kyo-shin-modal__close:hover { color: #1c1917; background: #f5f5f4; }
    .pds-kyo-shin-modal__body { padding: 20px 22px 8px; max-height: 68vh; overflow: auto; letter-spacing: 0; word-spacing: normal; }
    .pds-kyo-shin-modal__field { margin-bottom: 16px; }
    body.pds-admin .pds-kyo-shin-modal__label,
    .pds-kyo-shin-modal__label {
        display: block;
        margin-bottom: 8px !important;
        font-size: 13px !important;
        font-weight: 700 !important;
        color: #57534e !important;
        letter-spacing: 0;
        word-spacing: normal;
    }
    .pds-kyo-shin-modal__date { position: relative; }
    .pds-kyo-shin-modal__date i {
        position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #ea580c; pointer-events: none;
    }
    .pds-kyo-shin-modal__date input,
    .pds-kyo-shin-modal__text {
        width: 100%; height: 48px; padding: 0 16px; border: 1px solid #e7e5e4; border-radius: 14px;
        background: #fff; font-size: 15px; font-weight: 600; color: #1c1917; letter-spacing: 0; word-spacing: normal;
    }
    .pds-kyo-shin-modal__date input { padding-left: 42px; }
    .pds-kyo-shin-modal__date input:focus,
    .pds-kyo-shin-modal__text:focus {
        outline: none; border-color: #fb923c; box-shadow: 0 0 0 4px rgba(254, 111, 7, .12);
    }
    .pds-kyo-shin-modal__text.is-readonly,
    .pds-kyo-shin-modal__text[readonly] {
        background: #f8fafc;
        color: #334155;
        border-color: #e2e8f0;
        cursor: default;
        box-shadow: none;
    }
    .pds-kyo-shin-modal__text.is-readonly:focus,
    .pds-kyo-shin-modal__text[readonly]:focus {
        border-color: #e2e8f0;
        box-shadow: none;
    }
    .pds-kyo-shin-modal__methods {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 4px;
        padding: 4px;
        border-radius: 16px;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
    }
    body.pds-admin .pds-kyo-shin-modal__method,
    .pds-kyo-shin-modal__method {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 46px;
        margin: 0 !important;
        padding: 0 12px;
        border: 0;
        border-radius: 12px;
        background: transparent;
        font-size: 15px !important;
        font-weight: 800 !important;
        letter-spacing: 0 !important;
        word-spacing: normal;
        color: #64748b !important;
        cursor: pointer;
    }
    body.pds-admin .pds-kyo-shin-modal__method i,
    body.pds-admin .pds-kyo-shin-modal__method span,
    .pds-kyo-shin-modal__method i,
    .pds-kyo-shin-modal__method span { color: inherit !important; }
    body.pds-admin .pds-kyo-shin-modal__method.is-kpay.is-active,
    .pds-kyo-shin-modal__method.is-kpay.is-active {
        background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
        color: #fff !important;
        box-shadow: 0 6px 14px rgba(234, 88, 12, .28);
    }
    body.pds-admin .pds-kyo-shin-modal__method.is-cash.is-active,
    .pds-kyo-shin-modal__method.is-cash.is-active {
        background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
        color: #fff !important;
        box-shadow: 0 6px 14px rgba(2, 132, 199, .28);
    }
    .pds-kyo-shin-modal__upload {
        display: flex;
        align-items: center;
        gap: 14px;
        min-height: 84px;
        margin: 0;
        padding: 12px 14px;
        border: 1px dashed #fdba74;
        border-radius: 16px;
        background: #fffaf5;
        cursor: pointer;
    }
    .pds-kyo-shin-modal__upload:hover { border-color: #fb923c; background: #fff7ed; }
    .pds-kyo-shin-modal__upload-icon {
        width: 44px; height: 44px; border-radius: 12px; display: grid; place-items: center; flex-shrink: 0;
        background: linear-gradient(135deg, #f97316, #ea580c); color: #fff;
        box-shadow: 0 8px 16px rgba(234, 88, 12, .22);
    }
    .pds-kyo-shin-modal__upload-copy { min-width: 0; flex: 1; }
    body.pds-admin .pds-kyo-shin-modal__upload-title,
    .pds-kyo-shin-modal__upload-title {
        display: block; margin: 0 0 2px; font-size: 14px !important; font-weight: 800 !important;
        color: #9a3412 !important; letter-spacing: 0; word-spacing: normal;
    }
    body.pds-admin .pds-kyo-shin-modal__upload-hint,
    .pds-kyo-shin-modal__upload-hint {
        display: block; margin: 0; font-size: 12px !important; font-weight: 600 !important;
        color: #c2410c !important; letter-spacing: 0; word-spacing: normal;
    }
    .pds-kyo-shin-modal__upload input { display: none; }
    .pds-kyo-shin-modal__previews {
        display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px;
    }
    .pds-kyo-shin-modal__preview-item {
        position: relative; width: 72px; height: 72px; flex-shrink: 0;
    }
    .pds-kyo-shin-modal__preview-item img {
        width: 72px; height: 72px; object-fit: cover; border-radius: 10px;
        border: 1px solid #fed7aa; display: block;
    }
    .pds-kyo-shin-modal__preview-remove {
        position: absolute; top: -6px; right: -6px; width: 22px; height: 22px;
        border: 0; border-radius: 999px; background: #1c1917; color: #fff;
        display: grid; place-items: center; cursor: pointer; padding: 0; font-size: 11px;
        box-shadow: 0 2px 6px rgba(28, 25, 23, .25);
    }
    .pds-kyo-shin-modal__footer { display: flex; justify-content: flex-end; gap: 10px; padding: 16px 22px 22px; }
    body.pds-admin .pds-kyo-shin-modal__btn,
    .pds-kyo-shin-modal__btn {
        min-height: 44px; padding: 0 18px; border-radius: 999px; border: 0; font-weight: 700;
        letter-spacing: 0; word-spacing: normal;
    }
    .pds-kyo-shin-modal__btn--ghost { background: #f5f5f4; color: #44403c; }
    .pds-kyo-shin-modal__btn--ghost:hover { background: #e7e5e4; }
    .pds-kyo-shin-modal__btn--primary {
        background: linear-gradient(135deg, #FE6F07, #ff8f3d); color: #fff;
        box-shadow: 0 10px 18px rgba(254, 111, 7, .24);
    }
    .pds-kyo-shin-modal__btn--primary:disabled { opacity: .5; box-shadow: none; }
    .pds-kyo-shin-modal__amount {
        margin: 0 0 16px;
        padding: 14px 16px 12px;
        border-radius: 16px;
        border: 1px solid #fdba74;
        background: linear-gradient(180deg, #fff7ed 0%, #fff 100%);
    }
    body.pds-admin .pds-kyo-shin-modal__amount-label,
    .pds-kyo-shin-modal__amount-label {
        display: block;
        margin: 0 0 4px;
        font-size: 12px !important;
        font-weight: 700 !important;
        color: #c2410c !important;
        letter-spacing: 0;
    }
    body.pds-admin .pds-kyo-shin-modal__amount-total,
    .pds-kyo-shin-modal__amount-total {
        display: flex;
        align-items: baseline;
        gap: 6px;
        margin: 0;
        font-size: 26px !important;
        font-weight: 800 !important;
        color: #9a3412 !important;
        letter-spacing: 0;
        line-height: 1.15;
    }
    .pds-kyo-shin-modal__amount-total em {
        font-size: 13px;
        font-style: normal;
        font-weight: 700;
        color: #c2410c;
    }
    .pds-kyo-shin-modal__amount-list {
        list-style: none;
        margin: 10px 0 0;
        padding: 8px 0 0;
        border-top: 1px dashed #fed7aa;
    }
    .pds-kyo-shin-modal__amount-list:empty { display: none; }
    .pds-kyo-shin-modal__amount-list li {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 4px 0;
        font-size: 13px;
        font-weight: 700;
        color: #44403c;
    }
    .pds-kyo-shin-modal__amount-list strong { color: #9a3412; }
</style>

@php
    $kyoShinKpayName = $kyoShinKpayName ?? '';
    $kyoShinKpayNo = $kyoShinKpayNo ?? '';
@endphp

<div class="modal fade pds-dispatch-modal" id="kyoShinGiveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 520px;">
        <div class="modal-content pds-kyo-shin-modal">
            <div class="pds-kyo-shin-modal__header">
                <div class="pds-kyo-shin-modal__heading">
                    <span class="pds-kyo-shin-modal__icon" aria-hidden="true">
                        <i class="fas fa-hand-holding-usd"></i>
                    </span>
                    <div>
                        <h5 class="pds-kyo-shin-modal__title">{{ __('message.kyo_shin_give') }}</h5>
                        <span class="pds-kyo-shin-modal__count" id="kyoShinSelectedCountLabel">
                            {{ __('message.kyo_shin_selected_count', ['count' => 0]) }}
                        </span>
                    </div>
                </div>
                <button type="button" class="pds-kyo-shin-modal__close" data-dismiss="modal" aria-label="{{ __('message.close') }}">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>
            <div class="pds-kyo-shin-modal__body">
                <div class="pds-kyo-shin-modal__amount" id="kyoShinGiveAmountBox">
                    <span class="pds-kyo-shin-modal__amount-label">{{ __('message.kyo_shin_give_amount') }}</span>
                    <p class="pds-kyo-shin-modal__amount-total">
                        <strong id="kyoShinGiveAmountTotal">0</strong>
                        <em>Ks</em>
                    </p>
                    <ul class="pds-kyo-shin-modal__amount-list" id="kyoShinGiveAmountList"></ul>
                </div>
                <input type="hidden" id="kyo_shin_payment_method" value="kpay">
                <div class="pds-kyo-shin-modal__field">
                    <span class="pds-kyo-shin-modal__label">{{ __('message.kyo_shin_pay_method') }}</span>
                    <div class="pds-kyo-shin-modal__methods" role="tablist">
                        <button type="button" class="pds-kyo-shin-modal__method is-kpay is-active" data-method="kpay">
                            <i class="fas fa-mobile-alt" aria-hidden="true"></i>
                            <span>{{ __('message.kyo_shin_pay_kpay') }}</span>
                        </button>
                        <button type="button" class="pds-kyo-shin-modal__method is-cash" data-method="cash">
                            <i class="fas fa-money-bill-wave" aria-hidden="true"></i>
                            <span>{{ __('message.kyo_shin_pay_cash') }}</span>
                        </button>
                    </div>
                </div>
                <div class="pds-kyo-shin-modal__field">
                    <label class="pds-kyo-shin-modal__label" for="kyo_shin_due_finished_at">{{ __('message.kyo_shin_due_date') }}</label>
                    <div class="pds-kyo-shin-modal__date">
                        <i class="far fa-calendar-alt" aria-hidden="true"></i>
                        <input type="text" id="kyo_shin_due_finished_at" class="dispatch-datepicker" value="{{ now('Asia/Yangon')->addDays(7)->format('d-m-Y') }}" autocomplete="off">
                    </div>
                </div>
                <div id="kyoShinKpayFields">
                    <div class="pds-kyo-shin-modal__field">
                        <label class="pds-kyo-shin-modal__label" for="kyo_shin_kpay_name">{{ __('message.kpay_name') }}</label>
                        <input type="text" id="kyo_shin_kpay_name" class="pds-kyo-shin-modal__text is-readonly" value="{{ $kyoShinKpayName }}" data-default="{{ $kyoShinKpayName }}" readonly tabindex="-1" autocomplete="off">
                    </div>
                    <div class="pds-kyo-shin-modal__field">
                        <label class="pds-kyo-shin-modal__label" for="kyo_shin_kpay_no">{{ __('message.kpay_no') }}</label>
                        <input type="text" id="kyo_shin_kpay_no" class="pds-kyo-shin-modal__text is-readonly" value="{{ $kyoShinKpayNo }}" data-default="{{ $kyoShinKpayNo }}" readonly tabindex="-1" autocomplete="off">
                    </div>
                    <div class="pds-kyo-shin-modal__upload" data-input="#kyo_shin_kpay_slip">
                        <span class="pds-kyo-shin-modal__upload-icon" aria-hidden="true"><i class="fas fa-cloud-upload-alt"></i></span>
                        <span class="pds-kyo-shin-modal__upload-copy">
                            <span class="pds-kyo-shin-modal__upload-title">{{ __('message.kyo_shin_kpay_ss') }}</span>
                            <span class="pds-kyo-shin-modal__upload-hint" id="kyoShinKpayHint">{{ __('message.kyo_shin_upload_hint') }}</span>
                        </span>
                        <input type="file" id="kyo_shin_kpay_slip" accept="image/*" multiple>
                    </div>
                    <div class="pds-kyo-shin-modal__previews" id="kyoShinKpayPreviews"></div>
                </div>
                <div id="kyoShinCashFields" style="display:none;">
                    <div class="pds-kyo-shin-modal__upload" data-input="#kyo_shin_cash_slip">
                        <span class="pds-kyo-shin-modal__upload-icon" aria-hidden="true"><i class="fas fa-camera"></i></span>
                        <span class="pds-kyo-shin-modal__upload-copy">
                            <span class="pds-kyo-shin-modal__upload-title">{{ __('message.kyo_shin_cash_photo') }}</span>
                            <span class="pds-kyo-shin-modal__upload-hint" id="kyoShinCashHint">{{ __('message.kyo_shin_upload_hint') }}</span>
                        </span>
                        <input type="file" id="kyo_shin_cash_slip" accept="image/*" multiple>
                    </div>
                    <div class="pds-kyo-shin-modal__previews" id="kyoShinCashPreviews"></div>
                </div>
            </div>
            <div class="pds-kyo-shin-modal__footer">
                <button type="button" class="pds-kyo-shin-modal__btn pds-kyo-shin-modal__btn--ghost" data-dismiss="modal">{{ __('message.cancel') }}</button>
                <button type="button" class="pds-kyo-shin-modal__btn pds-kyo-shin-modal__btn--primary" id="confirmKyoShinGiveBtn">{{ __('message.kyo_shin_give') }}</button>
            </div>
        </div>
    </div>
</div>
