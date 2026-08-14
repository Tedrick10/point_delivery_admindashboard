@php
    $enabled = (bool) $enabled;
@endphp
<span class="pds-status-pill {{ $enabled ? 'pds-status-pill--on' : 'pds-status-pill--off' }}">
    <i class="fas fa-circle mr-1" style="font-size: 0.45rem; vertical-align: middle;"></i>
    {{ $enabled ? __('message.enable') : __('message.disable') }}
</span>
