@php
    $displayName = trim((string) ($row->display_name ?? $row->name ?? ''));
    $cityName = trim((string) ($row->city_name ?? ''));
    $nameWithCity = $cityName !== ''
        ? ($displayName.' ('.$cityName.')')
        : ($displayName !== '' ? $displayName : (string) ($row->name ?? ''));
    $initial = mb_strtoupper(mb_substr($displayName !== '' ? $displayName : 'O', 0, 1));
    if ($initial === '#') {
        $initial = '#';
    }
    $phone = trim((string) ($row->phone ?? ''));
    $amount = (float) ($row->kyo_shin_amount ?? abs((float) ($row->amount ?? 0)));
    $itemCount = (int) ($row->item_count ?? 0);
@endphp
<tr
    class="pds-os-settlement-row pds-os-kyo-shin-settlement-row"
    data-os-id="{{ $row->id }}"
    data-has-kpay="1"
    data-is-finished="0"
    data-payment-method="cash"
    data-section="kyo_shin"
>
    <td class="pds-rider-col-no">{{ $index + 1 }}</td>
    <td class="pds-os-settlement-col-os">
        <div class="pds-rider-person">
            <span class="pds-rider-avatar pds-os-settlement-avatar" aria-hidden="true">{{ $initial }}</span>
            <div class="pds-rider-person__meta">
                <div class="pds-dispatch-rider-list-name">{{ $nameWithCity }}</div>
                @if($phone !== '' && $phone !== '-')
                    <div class="pds-dispatch-rider-list-phone">{{ $phone }}</div>
                @endif
            </div>
        </div>
    </td>
    <td class="pds-os-settlement-col-amount text-right">
        <span class="pds-os-amount-pill is-negative">{{ number_format($amount) }}</span>
    </td>
    <td class="text-right">0</td>
    <td class="text-right">{{ number_format($amount) }}</td>
    <td class="text-center">
        @if($itemCount > 0)
            <a
                href="{{ route('order.dispatch.os-kyo-shin-items', [
                    'osId' => $row->id,
                    'from_date' => $filterFromDate ?? request('from_date'),
                    'to_date' => $filterToDate ?? request('to_date'),
                    'branch_id' => $selectedBranchId ?? request('branch_id'),
                ]) }}"
                class="pds-rider-count pds-rider-count--kyo-shin is-link"
                title="{{ __('message.kyo_shin_details') }}"
            >{{ $itemCount }}</a>
        @else
            <span class="pds-rider-count pds-rider-count--muted">0</span>
        @endif
    </td>
    <td class="pds-os-settlement-col-action">
        <button
            type="button"
            class="pds-os-finish-btn"
            data-os-id="{{ $row->id }}"
            data-has-kpay="1"
        >
            <i class="fas fa-check" aria-hidden="true"></i>
            <span>{{ __('message.finished') }}</span>
        </button>
    </td>
</tr>
