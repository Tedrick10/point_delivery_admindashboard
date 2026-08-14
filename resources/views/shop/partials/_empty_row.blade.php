@php
    $colspan = $colspan ?? 5;
    $icon = $icon ?? 'fa-box-open';
    $message = $message ?? __('message.no_record_found');
@endphp
<tr>
    <td colspan="{{ $colspan }}">
        <div class="pds-shop-empty">
            <div class="pds-shop-empty__icon"><i class="fas {{ $icon }}"></i></div>
            <p class="pds-shop-empty__title">{{ $message }}</p>
            @if(!empty($actionRoute) && !empty($actionPermission) && auth()->user()->can($actionPermission))
                <a href="{{ route($actionRoute) }}" class="btn btn-sm btn-primary mt-2">
                    <i class="fa fa-plus-circle mr-1"></i> {{ $actionLabel ?? __('message.add') }}
                </a>
            @endif
        </div>
    </td>
</tr>
