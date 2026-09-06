@php
    $deliveredPhotoUrl = function_exists('dispatchItemProofPhotoUrl')
        ? dispatchItemProofPhotoUrl($item, 'delivered')
        : null;
    $deliveredType = trim((string) ($item->delivered_type ?? ''));
@endphp
@if($deliveredPhotoUrl || $deliveredType !== '')
    <div class="pds-delivered-proof">
        @if($deliveredType === 'gate')
            <div class="pds-delivered-proof__type">{{ __('message.delivered_type_gate') }}</div>
        @elseif($deliveredType === 'other')
            <div class="pds-delivered-proof__type">{{ __('message.delivered_type_other') }}</div>
        @endif
        @if($deliveredPhotoUrl)
            <a href="{{ $deliveredPhotoUrl }}" target="_blank" rel="noopener" class="d-inline-block mt-1">
                <img src="{{ $deliveredPhotoUrl }}" alt="{{ __('message.delivered_image') }}" style="width: 88px; height: 88px; object-fit: cover; border-radius: 10px; border: 1px solid #e5e7eb; background: #fff;">
            </a>
        @endif
    </div>
@endif
