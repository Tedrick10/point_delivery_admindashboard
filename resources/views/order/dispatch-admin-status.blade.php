@php
    $workflow = app(\App\Services\DispatchOrderWorkflowService::class);
    $label = $workflow->adminStatusLabel($order);
    $class = $workflow->adminStatusClass($order);
@endphp

<span class="pds-dispatch-status {{ $class }}">{{ $label }}</span>
