@php
    $showRemark = $showRemark ?? false;
    $disableOsCredit = $disableOsCredit ?? false;
    $isReturnContext = $isReturnContext ?? false;
    $returnType = $returnType ?? null;
    $kyoShinAmountLocked = $kyoShinAmountLocked ?? false;
    $kyoShinItemValueLocked = $kyoShinItemValueLocked ?? false;
    $kyoShinDeliAmountLocked = $kyoShinDeliAmountLocked ?? $kyoShinAmountLocked;
    // Admin Return parcels show Customer information (Rider App shows Os Information).
    $lockItemValue = $kyoShinAmountLocked || $kyoShinItemValueLocked;
    $lockDeliAmount = $kyoShinDeliAmountLocked || ($isReturnContext && $returnType === 'normal');
    $contactNameLabel = __('message.customer_name');
    $contactPhoneLabel = __('message.phone');
    $contactAddressLabel = __('message.address');
    $sectionTitle = __('message.payment_customer');
    $subheadTitle = __('message.customer');
@endphp

<section class="pds-dispatch-item-section">
    <header class="pds-dispatch-item-section-head">
        <i class="fas fa-wallet"></i>
        <span>{{ $sectionTitle }}</span>
    </header>
    <div class="pds-dispatch-item-section-body">
        <div class="pds-dispatch-item-subhead">{{ $subheadTitle }}</div>
        <div class="pds-dispatch-grid pds-dispatch-grid-2 pds-dispatch-item-grid">
            <div class="pds-dispatch-field">
                <label for="customer_name">{{ $contactNameLabel }}</label>
                <input type="text" name="customer_name" id="customer_name" class="pds-dispatch-input" value="{{ $customerName }}">
            </div>
            <div class="pds-dispatch-field">
                <label for="customer_phone">{{ $contactPhoneLabel }}</label>
                <input type="text" name="customer_phone" id="customer_phone" class="pds-dispatch-input" value="{{ $customerPhone }}" autocomplete="off">
            </div>
            <div class="pds-dispatch-field pds-dispatch-grid-span-2">
                <label for="customer_address">{{ $contactAddressLabel }}</label>
                <input type="text" name="customer_address" id="customer_address" class="pds-dispatch-input" value="{{ $customerAddress }}">
            </div>
        </div>

        <div class="pds-dispatch-item-subhead">{{ __('message.values') }}</div>
        <div class="pds-dispatch-grid pds-dispatch-grid-2 pds-dispatch-item-grid">
            <div class="pds-dispatch-field">
                <label for="item_value">{{ __('message.item_value') }}</label>
                <input type="number" name="item_value" id="item_value" class="pds-dispatch-input pds-dispatch-item-amount" min="0" step="1" value="{{ $itemValue }}" @if($lockItemValue) readonly @endif>
            </div>
            <div class="pds-dispatch-field">
                @if($isReturnContext && $returnType === 'normal')
                    <label for="deli_amount" class="pds-return-deli-hint">{{ __('message.return_normal_deli_not_needed') }}</label>
                @else
                    <label for="deli_amount">{{ __('message.deli_amount') }}</label>
                @endif
                <input type="number" name="deli_amount" id="deli_amount" class="pds-dispatch-input pds-dispatch-item-amount" min="0" step="1" value="{{ $isReturnContext && $returnType === 'normal' ? 0 : $deliAmount }}" @if($lockDeliAmount) readonly @endif>
            </div>
        </div>
        @if($isReturnContext)
            <input type="hidden" name="return_type" id="return_type" value="{{ $returnType }}">
        @endif

        @php
            $creditMode = $creditTo === 'customer'
                ? 'customer'
                : (((float) $osPaid > 0) ? 'os_paid' : 'os');
        @endphp
        <div class="pds-dispatch-item-credit">
            <span class="pds-dispatch-item-credit-label">{{ __('message.credit_holder') }}</span>
            <div class="pds-dispatch-item-credit-group" role="radiogroup">
                <label class="pds-dispatch-item-credit-pill @if($disableOsCredit) is-disabled @endif">
                    <input type="radio" name="credit_to" value="os" @checked($creditMode === 'os') @disabled($disableOsCredit)>
                    <span>Os</span>
                </label>
                <label class="pds-dispatch-item-credit-pill">
                    <input type="radio" name="credit_to" value="customer" @checked($creditMode === 'customer')>
                    <span>{{ __('message.customer') }}</span>
                </label>
                <label class="pds-dispatch-item-credit-pill @if($disableOsCredit) is-disabled @endif">
                    <input type="radio" name="credit_to" value="os_paid" @checked($creditMode === 'os_paid') @disabled($disableOsCredit)>
                    <span>{{ __('message.os_paid') }}</span>
                </label>
            </div>
        </div>

        <div class="pds-dispatch-item-subhead">{{ __('message.amounts') }}</div>
        <div class="pds-dispatch-grid pds-dispatch-grid-3 pds-dispatch-item-grid">
            <div class="pds-dispatch-field pds-dispatch-grid-span-3">
                <label for="item_weight">{{ __('message.size') }}</label>
                <input type="hidden" name="weight" id="item_weight" value="{{ $weight }}">
                <div class="pds-dispatch-item-size-grid" role="radiogroup" aria-label="{{ __('message.size') }}">
                    @for($size = 1; $size <= 10; $size++)
                        <button
                            type="button"
                            class="pds-dispatch-item-size-pill @if((int) $weight === $size) is-selected @endif"
                            data-size-value="{{ $size }}"
                            aria-pressed="{{ (int) $weight === $size ? 'true' : 'false' }}"
                        >Size({{ $size }})</button>
                    @endfor
                </div>
            </div>
            <div class="pds-dispatch-field">
                <label for="advance_paid">{{ __('message.advance_paid') }}</label>
                <input type="number" name="advance_paid" id="advance_paid" class="pds-dispatch-input pds-dispatch-item-amount" min="0" step="1" value="{{ $advancePaid }}">
            </div>
            <div class="pds-dispatch-field" id="os_paid_field_wrap"@if($creditMode !== 'os_paid') style="display:none"@endif>
                <label for="os_paid">{{ __('message.os_paid') }}</label>
                <input type="number" name="os_paid" id="os_paid" class="pds-dispatch-input pds-dispatch-item-amount" min="0" step="1" value="{{ $osPaid }}">
            </div>
        </div>

        @if($showRemark)
            <div class="pds-dispatch-field pds-dispatch-item-photo-remark">
                <label for="item_remark">{{ __('message.remark_label') }}</label>
                <textarea name="remark" id="item_remark" class="pds-dispatch-input pds-dispatch-textarea pds-dispatch-item-textarea" rows="3" placeholder="{{ __('message.remark_label') }}">{{ $remark }}</textarea>
            </div>
        @endif
    </div>
</section>
