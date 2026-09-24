@php
    $order = $item->order;
    $osId = (int) ($order?->client_id ?? 0);
    $osName = resolveDispatchOsName($order);
    if ($osName === '-' || $osName === '') {
        $osName = $osId > 0 ? ('#'.$osId) : __('message.no_os');
    }
    $cityName = trim((string) ($order?->client?->city?->name ?? ''));
    if ($cityName !== '') {
        $osName .= ' ('.$cityName.')';
    }
    $township = \App\Models\DispatchOrderItem::deliveryCityLabel($item->delivery_city);
    if ($township === '-' && $item->township) {
        $township = $item->township;
    }
    $date = $item->received_date
        ? \Carbon\Carbon::parse($item->received_date)->format('d-m-Y')
        : ($item->admin_completed_at
            ? \Carbon\Carbon::parse($item->admin_completed_at)->timezone('Asia/Yangon')->format('d-m-Y')
            : '-');
    $amount = $item->kyoShinPayAmount();
@endphp
<tr
    class="pds-os-settlement-row"
    data-os-id="{{ $osId }}"
    data-item-id="{{ $item->id }}"
    data-has-kpay="1"
    data-is-finished="0"
    data-payment-method="cash"
    data-section="kyo_shin"
    data-amount="{{ $amount }}"
>
    <td class="pds-rider-col-no">{{ $index + 1 }}</td>
    <td class="pds-os-settlement-col-os">{{ $osName }}</td>
    <td><span class="pds-rider-code">{{ $item->code ?? '-' }}</span></td>
    <td>{{ $item->customer_name ?: '-' }}</td>
    <td>{{ $item->customer_phone ?: '-' }}</td>
    <td>{{ $township }}</td>
    <td>{{ $date }}</td>
    <td class="pds-os-settlement-col-amount text-right">
        <span class="pds-os-amount-pill is-receive">{{ number_format($amount) }}</span>
    </td>
    <td class="pds-os-settlement-col-action">
        <button
            type="button"
            class="pds-os-finish-btn"
            data-os-id="{{ $osId }}"
            data-item-id="{{ $item->id }}"
        >
            <i class="fas fa-check" aria-hidden="true"></i>
            <span>{{ __('message.finished') }}</span>
        </button>
    </td>
</tr>
