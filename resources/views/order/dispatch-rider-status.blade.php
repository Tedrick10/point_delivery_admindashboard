@php
    $workflow = app(\App\Services\DispatchOrderWorkflowService::class);
    $label = $workflow->riderStatusLabel($order);
    $class = $workflow->riderStatusClass($order);
    $isPickupError = ($order->status ?? '') === 'pickup_error';
    $isPickupErrorCancelled = $workflow->isPickupErrorCancelled($order);
    $choice = (string) ($order->pickup_error_choice ?? '');
    $choiceLabel = ($isPickupError || $isPickupErrorCancelled)
        ? pickupErrorChoiceLabel($choice ?: null)
        : null;
    // Cancel / Express: show only the user-choice line (no "Pick Up Error" header).
    $showChoiceOnly = $choiceLabel && in_array($choice, ['cancel', 'express'], true);
@endphp

@if($showChoiceOnly)
    <span class="pds-dispatch-status {{ $class }}" title="{{ $choiceLabel }}">{{ $choiceLabel }}</span>
@elseif($isPickupError)
    <div class="pds-rider-error-chip" title="{{ $label }}">
        <div class="pds-rider-error-chip__status">
            <span class="pds-rider-error-chip__dot" aria-hidden="true"></span>
            <span class="pds-rider-error-chip__label">{{ $label }}</span>
        </div>
    </div>
@else
    <span class="pds-dispatch-status {{ $class }}">{{ $label }}</span>
@endif
