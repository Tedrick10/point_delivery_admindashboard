@php
    $needsPickupErrorRider = ($order->status ?? '') === 'pickup_error'
        && in_array($order->pickup_error_choice ?? '', ['express', 'next_day'], true);
    $currentRiderId = $needsPickupErrorRider ? '' : (string) ($order->delivery_man_id ?? '');
    $currentRiderName = $needsPickupErrorRider ? '' : (optional($order->delivery_man)->name ?? '');
    $isAssigned = $currentRiderId !== '';
    $pickupRiders = $pickupRiders ?? collect();
    $pickupPlaceholder = $needsPickupErrorRider
        ? __('message.pickup_error_reassign_rider')
        : orderPickupRiderPlaceholder($order);
@endphp

<div class="pds-dispatch-pickup-rider-cell {{ $isAssigned ? 'is-assigned' : 'is-unassigned' }}{{ $needsPickupErrorRider ? ' needs-pickup-error-rider' : '' }}" data-order-id="{{ $order->id }}">
    <select class="pds-dispatch-input pds-dispatch-select dispatch-pickup-rider-select2"
            data-order-id="{{ $order->id }}"
            data-current="{{ $currentRiderId }}"
            data-current-name="{{ $currentRiderName }}"
            data-placeholder="{{ $pickupPlaceholder }}"
            data-force-assign="{{ $needsPickupErrorRider ? '1' : '0' }}"
            aria-label="{{ __('message.pickup_rider') }}"
            required>
        <option value=""></option>
        @foreach(($pickupRiders ?? collect()) as $rider)
            @php
                $riderPhone = method_exists($rider, 'riderAssignedPhone')
                    ? ($rider->riderAssignedPhone() ?: '')
                    : '';
                $riderLabel = trim($rider->name . ($riderPhone !== '' ? ' · ' . $riderPhone : ''));
            @endphp
            <option value="{{ $rider->id }}" @selected($currentRiderId === (string) $rider->id)>{{ $riderLabel }}</option>
        @endforeach
        @if($currentRiderId && !($pickupRiders ?? collect())->contains('id', (int) $currentRiderId))
            @php
                $currentPhone = optional($order->delivery_man)->riderAssignedPhone() ?: '';
                $currentLabel = trim(($currentRiderName ?: $currentRiderId) . ($currentPhone !== '' ? ' · ' . $currentPhone : ''));
            @endphp
            <option value="{{ $currentRiderId }}" selected>{{ $currentLabel }}</option>
        @endif
    </select>
</div>
