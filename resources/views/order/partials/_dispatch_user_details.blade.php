@php
    $pickupName = $pickup['name'] ?? '-';
    $pickupPhone = normalizeContactNumber($pickup['contact_number'] ?? '');
    $pickupAddress = $pickup['address'] ?? '-';
    $deliveryName = $delivery['name'] ?? '-';
    $deliveryPhone = normalizeContactNumber($delivery['contact_number'] ?? '');
    $deliveryAddress = $delivery['address'] ?? '-';
    $isSelfOrder = (int) ($isSelfOrder ?? 1) === 1;
    $resolvedOrder = $order ?? null;
    $osName = $resolvedOrder ? resolveDispatchOsName($resolvedOrder) : $pickupName;
    $osPhone = $resolvedOrder ? resolveDispatchOsPhone($resolvedOrder) : ($pickupPhone !== '' ? $pickupPhone : '-');
    $osAddress = $resolvedOrder ? resolveDispatchOsAddress($resolvedOrder) : $pickupAddress;
    if ($osName === '-') {
        $osName = '';
    }
    if ($osPhone === '-') {
        $osPhone = '';
    }
    if ($osAddress === '-') {
        $osAddress = '';
    }
@endphp

<section class="pds-dispatch-section">
    <div class="pds-dispatch-section-head">
        <h2 class="pds-dispatch-section-title">
            <i class="fas fa-receipt" aria-hidden="true"></i>
            {{ __('message.user_submitted_details') }}
        </h2>
        @if(!empty($orderType['label']))
            <span class="pds-order-type-badge {{ $orderType['class'] ?? '' }}">{{ $orderType['label'] }}</span>
        @endif
    </div>

    @if($isShopOrder)
        <div class="pds-dispatch-user-detail-grid pds-dispatch-user-detail-grid-single">
            <div class="pds-pickup-contact-card">
                <div class="pds-pickup-contact-head">
                    <div class="pds-pickup-contact-head-icon">
                        <i class="fas fa-store" aria-hidden="true"></i>
                    </div>
                    <div>
                        <p class="pds-pickup-contact-title">{{ __('message.pickup_from_shop') }}</p>
                        <p class="pds-pickup-contact-subtitle">{{ __('message.shop_pickup_details') }}</p>
                    </div>
                </div>

                <div class="pds-pickup-contact-body">
                    <div class="pds-pickup-contact-item">
                        <div class="pds-pickup-contact-item-icon">
                            <i class="fas fa-user" aria-hidden="true"></i>
                        </div>
                        <div class="pds-pickup-contact-item-content">
                            <span class="pds-pickup-contact-item-label">{{ __('message.name') }}</span>
                            <span class="pds-pickup-contact-item-value">{{ $pickupName }}</span>
                        </div>
                    </div>

                    <div class="pds-pickup-contact-item">
                        <div class="pds-pickup-contact-item-icon">
                            <i class="fas fa-phone-alt" aria-hidden="true"></i>
                        </div>
                        <div class="pds-pickup-contact-item-content">
                            <span class="pds-pickup-contact-item-label">{{ __('message.phone') }}</span>
                            @if($pickupPhone !== '')
                                <a href="tel:{{ $pickupPhone }}" class="pds-pickup-contact-item-value pds-pickup-contact-phone">{{ $pickupPhone }}</a>
                            @else
                                <span class="pds-pickup-contact-item-value">-</span>
                            @endif
                        </div>
                    </div>

                    <div class="pds-pickup-contact-item">
                        <div class="pds-pickup-contact-item-icon">
                            <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                        </div>
                        <div class="pds-pickup-contact-item-content">
                            <span class="pds-pickup-contact-item-label">{{ __('message.address') }}</span>
                            <span class="pds-pickup-contact-item-value">{{ $pickupAddress }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" name="client_id" value="{{ $clientId }}">
        <input type="hidden" name="os_name" value="{{ $osName }}">
        <input type="hidden" name="os_phone" value="{{ $osPhone }}">
        <input type="hidden" name="os_address" value="{{ $osAddress }}">
    @elseif($isGateOrder)
        <div class="pds-gate-pass-showcase">
            @if(!empty($gatePassImages))
                @foreach($gatePassImages as $image)
                    <div class="pds-gate-pass-card">
                        <div class="pds-gate-pass-card-head">
                            <div class="pds-gate-pass-card-icon">
                                <i class="fas fa-id-card" aria-hidden="true"></i>
                            </div>
                            <div>
                                <p class="pds-gate-pass-card-label">{{ __('message.gate_pass_photo') }}</p>
                                <p class="pds-gate-pass-card-hint">{{ __('message.click_to_view_full_size') }}</p>
                            </div>
                        </div>
                        <a href="{{ $image['url'] }}"
                           class="pds-gate-pass-frame pds-dispatch-gate-photo-lightbox"
                           title="{{ $image['name'] ?? __('message.gate_pass_photo') }}">
                            <img src="{{ $image['url'] }}" alt="{{ $image['name'] ?? __('message.gate_pass_photo') }}">
                            <span class="pds-gate-pass-zoom">
                                <i class="fas fa-search-plus" aria-hidden="true"></i>
                            </span>
                        </a>
                        @if(!empty($image['name']))
                            <p class="pds-gate-pass-filename">{{ $image['name'] }}</p>
                        @endif
                    </div>
                @endforeach
            @else
                <div class="pds-gate-pass-empty">
                    <div class="pds-gate-pass-empty-icon">
                        <i class="fas fa-image" aria-hidden="true"></i>
                    </div>
                    <p class="pds-gate-pass-empty-title">{{ __('message.no_photo_uploaded') }}</p>
                    <p class="pds-gate-pass-empty-text">{{ __('message.gate_pass_photo') }}</p>
                </div>
            @endif
        </div>

        <input type="hidden" name="client_id" value="{{ $clientId }}">
        <input type="hidden" name="os_name" value="{{ $osName }}">
        <input type="hidden" name="os_phone" value="{{ $osPhone }}">
        <input type="hidden" name="os_address" value="{{ $osAddress }}">
    @endif
</section>
