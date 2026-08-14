@php
    $currentStatus = $order->status;
    $label = __('message.pick_up_only');
    $class = 'pds-dispatch-status-pickup';

    switch ($currentStatus) {
        case 'create':
            $label = __('message.pick_up_only');
            $class = 'pds-dispatch-status-pickup';
            break;
        case 'completed':
            $label = __('message.delivered');
            $class = 'pds-dispatch-status-delivered';
            break;
        case 'courier_assigned':
            $label = __('message.assigned');
            $class = 'pds-dispatch-status-assigned';
            break;
        case 'courier_picked_up':
            $label = __('message.picked_up');
            $class = 'pds-dispatch-status-picked';
            break;
        case 'cancelled':
            $label = __('message.cancelled');
            $class = 'pds-dispatch-status-cancelled';
            break;
        default:
            $label = ucfirst(str_replace('_', ' ', $currentStatus));
            $class = 'pds-dispatch-status-default';
    }
@endphp

<span class="pds-dispatch-status {{ $class }}">{{ $label }}</span>
