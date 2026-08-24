@php
    $initial = mb_strtoupper(mb_substr(trim($row->name) ?: 'O', 0, 1));
    $section = $section ?? 'pay'; // pay | receive
    $showKpayCols = $section === 'pay';
    $isDemo = ! empty($row->is_demo);
    $slipLabel = $section === 'receive' ? __('message.os_settlement_qr') : __('message.kpay_slip');
    $slipUploadLabel = $section === 'receive' ? __('message.upload_os_settlement_qr') : __('message.upload_kpay_slip');
@endphp
<tr
    class="pds-os-settlement-row {{ $isDemo ? 'is-demo' : '' }}"
    data-os-id="{{ $row->id }}"
    data-has-kpay="{{ $row->has_kpay_slip ? '1' : '0' }}"
    data-is-finished="0"
    data-payment-method="{{ $showKpayCols ? 'kpay' : 'cash' }}"
    data-section="{{ $section }}"
    data-is-demo="{{ $isDemo ? '1' : '0' }}"
>
    <td class="pds-rider-col-no">{{ $index + 1 }}</td>
    <td class="pds-os-settlement-col-os">
        <div class="pds-rider-person">
            <span class="pds-rider-avatar pds-os-settlement-avatar" aria-hidden="true">{{ $initial }}</span>
            <div class="pds-rider-person__meta">
                <div class="pds-dispatch-rider-list-name">{{ $row->name }}</div>
                @if($row->phone && $row->phone !== '-')
                    <div class="pds-dispatch-rider-list-phone">{{ $row->phone }}</div>
                @endif
                <span class="pds-os-settlement-badge">{{ $row->item_count }} {{ __('message.items') }}</span>
            </div>
        </div>
    </td>
    <td class="pds-os-settlement-col-amount text-right">
        <span class="pds-os-amount-pill {{ $row->amount < 0 ? 'is-negative' : 'is-receive' }}">
            {{ number_format($row->amount) }}
        </span>
    </td>
    @if($showKpayCols)
        <td class="pds-os-settlement-col-kpay">
            @if($row->kpay_name)
                <span class="pds-os-kpay-value">{{ $row->kpay_name }}</span>
            @else
                <span class="pds-os-kpay-empty">—</span>
            @endif
        </td>
        <td class="pds-os-settlement-col-kpay">
            @if($row->kpay_no)
                <span class="pds-os-kpay-value pds-os-kpay-value--mono">{{ $row->kpay_no }}</span>
            @else
                <span class="pds-os-kpay-empty">—</span>
            @endif
        </td>
    @endif
    <td class="pds-os-settlement-col-slip pds-os-kpay-upload-cell">
        <label class="pds-os-kpay-upload-card {{ $section === 'receive' ? 'pds-os-kpay-upload-card--qr' : '' }} {{ $isDemo ? 'is-demo' : '' }}" title="{{ $isDemo ? 'Demo' : $slipUploadLabel }}">
            @if($row->kpay_slip_url)
                <a href="{{ $row->kpay_slip_url }}" target="_blank" rel="noopener" class="pds-os-kpay-preview" onclick="event.preventDefault(); event.stopPropagation(); window.open(this.href, '_blank', 'noopener');">
                    <img src="{{ $row->kpay_slip_url }}" alt="{{ $slipLabel }}" class="pds-os-kpay-thumb">
                </a>
            @else
                <span class="pds-os-kpay-placeholder">
                    <i class="fas {{ $section === 'receive' ? 'fa-qrcode' : 'fa-image' }}" aria-hidden="true"></i>
                    <span>{{ $isDemo ? 'Demo' : $slipUploadLabel }}</span>
                </span>
            @endif
            @unless($isDemo)
                <span class="pds-os-kpay-upload-btn" aria-hidden="true">
                    <i class="fas fa-cloud-upload-alt"></i>
                </span>
                <input
                    type="file"
                    class="pds-os-kpay-file-input"
                    accept="image/*"
                    data-os-id="{{ $row->id }}"
                >
            @endunless
        </label>
    </td>
    @if($showKpayCols)
        <td class="pds-os-settlement-col-method">
            <div class="pds-os-pay-method js-os-row-pay-method" data-method="kpay">
                <button type="button" class="pds-os-pay-method__btn is-active" data-method="kpay">Kpay</button>
                <button type="button" class="pds-os-pay-method__btn" data-method="cash">{{ __('message.cash') }}</button>
            </div>
        </td>
    @endif
    <td class="pds-os-settlement-col-preview">
        <button
            type="button"
            class="pds-os-slip-preview-btn"
            data-os-id="{{ $row->id }}"
            data-is-demo="{{ $isDemo ? '1' : '0' }}"
            title="{{ __('message.show_slip_completed') }}"
            @disabled($isDemo)
        >
            <i class="fas fa-eye" aria-hidden="true"></i>
            <span>{{ $isDemo ? 'Demo' : __('message.show_slip_completed') }}</span>
        </button>
    </td>
    <td class="pds-os-settlement-col-action">
        <button
            type="button"
            class="pds-os-finish-btn {{ ($row->has_kpay_slip && ! $isDemo) ? '' : 'is-disabled' }}"
            data-os-id="{{ $row->id }}"
            data-has-kpay="{{ $row->has_kpay_slip ? '1' : '0' }}"
            data-is-demo="{{ $isDemo ? '1' : '0' }}"
            @disabled($isDemo || ! $row->has_kpay_slip)
        >
            <i class="fas fa-check" aria-hidden="true"></i>
            <span>{{ $isDemo ? 'Demo' : __('message.finished') }}</span>
        </button>
    </td>
</tr>
