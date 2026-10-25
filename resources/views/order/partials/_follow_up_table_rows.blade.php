@php
    $workflow = $workflow ?? app(\App\Services\DispatchOrderWorkflowService::class);
    $canReassignFollowUpRider = $canReassignFollowUpRider ?? false;
    $rowOffset = (int) ($rowOffset ?? 0);
@endphp
@forelse($items as $index => $item)
    @php
        $order = $item->order;
        $osName = resolveDispatchOsName($order);
        $osPhone = resolveDispatchOsPhone($order);
        $osAddress = resolveDispatchOsAddress($order);
        $pickupRider = optional($order?->delivery_man)->name ?? '-';
        $pickupRiderPhone = optional($order?->delivery_man)->riderAssignedPhone() ?: '';
        $deliveryRider = optional($item->deliveryMan)->name ?? '-';
        $fromTo = trim((optional($item->fromBranch)->name ?? '-') . ' - ' . (optional($item->toBranch)->name ?? '-'));
        $township = \App\Models\DispatchOrderItem::deliveryCityLabel($item->delivery_city);
        if ($township === '-' && $item->township) {
            $township = $item->township;
        }
        $orderDate = $order?->created_at
            ? \Carbon\Carbon::parse($order->created_at)->format('d-m-Y')
            : '-';
        $receivedDate = $item->received_date
            ? \Carbon\Carbon::parse($item->received_date)->format('d-m-Y')
            : '-';
        $followUpStatus = $workflow->followUpStatusKey($item);
        $rowCanReassign = $canReassignFollowUpRider && $workflow->canReassignFollowUpRider($item);
    @endphp
    <tr
        data-item-id="{{ $item->id }}"
        data-os-name="{{ $osName !== '-' ? $osName : '' }}"
        data-os-phone="{{ $osPhone !== '-' ? $osPhone : '' }}"
        data-pickup-rider="{{ $pickupRider !== '-' ? $pickupRider : '' }}"
        data-pickup-rider-phone="{{ $pickupRiderPhone }}"
        data-customer-name="{{ $item->customer_name ?: '' }}"
        data-customer-phone="{{ $item->customer_phone ?: '' }}"
        data-follow-up-status="{{ $followUpStatus }}"
        data-can-reassign="{{ $rowCanReassign ? '1' : '0' }}"
        data-to-branch-id="{{ (int) ($item->to_branch_id ?? 0) }}"
    >
        @if($canReassignFollowUpRider)
            <td class="pds-fu-col pds-fu-col--check pds-follow-up-check-col">
                @if($rowCanReassign)
                    <input type="checkbox"
                           class="pds-follow-up-item-check"
                           value="{{ $item->id }}"
                           aria-label="{{ __('message.follow_up_assign_rider') }}">
                @endif
            </td>
        @endif
        <td class="pds-fu-col pds-fu-col--no to-assign-row-no">{{ $rowOffset + $index + 1 }}</td>
        <td class="pds-fu-col pds-fu-col--date">{{ $orderDate }}</td>
        <td class="pds-fu-col pds-fu-col--date">{{ $receivedDate }}</td>
        <td class="pds-fu-col pds-fu-col--voucher">
            {{ $item->code ?? '-' }}
            @if($item->kyoShinItem)
                <span class="pds-kyo-shin-row-badge">{{ __('message.kyo_shin_title') }}</span>
            @endif
        </td>
        <td class="pds-fu-col pds-fu-col--status pds-follow-up-status-cell">
            <span class="pds-dispatch-status {{ $workflow->followUpItemStatusClass($item) }}" title="{{ $workflow->followUpItemStatusLabel($item) }}">{{ $workflow->followUpItemStatusLabel($item) }}</span>
        </td>
        <td class="pds-fu-col pds-fu-col--fromto">{{ $fromTo }}</td>
        <td class="pds-fu-col pds-fu-col--os"><span class="pds-follow-up-cell pds-follow-up-cell--name" title="{{ $osName }}">{{ $osName }}</span></td>
        <td class="pds-fu-col pds-fu-col--os-addr pds-follow-up-address-cell" title="{{ $osAddress }}">
            <span class="pds-follow-up-cell pds-follow-up-cell--address">{{ stringLong($osAddress, 'title', 28) ?: '-' }}</span>
        </td>
        <td class="pds-fu-col pds-fu-col--pickup"><span class="pds-follow-up-cell pds-follow-up-cell--rider" title="{{ $pickupRider }}">{{ $pickupRider }}</span></td>
        <td class="pds-fu-col pds-fu-col--customer"><span class="pds-follow-up-cell pds-follow-up-cell--name" title="{{ $item->customer_name }}">{{ $item->customer_name ?: '-' }}</span></td>
        <td class="pds-fu-col pds-fu-col--phone"><span class="pds-follow-up-cell pds-follow-up-cell--phone">{{ $item->customer_phone ?: '-' }}</span></td>
        <td class="pds-fu-col pds-fu-col--addr pds-follow-up-address-cell" title="{{ $item->customer_address }}">
            <span class="pds-follow-up-cell pds-follow-up-cell--address">{{ stringLong($item->customer_address ?? '', 'title', 28) ?: '-' }}</span>
        </td>
        <td class="pds-fu-col pds-fu-col--township">{{ $township }}</td>
        <td class="pds-fu-col pds-fu-col--rider">
            @if($rowCanReassign)
                <button type="button"
                        class="pds-follow-up-rider-btn js-follow-up-assign-one"
                        data-item-id="{{ $item->id }}"
                        data-to-branch-id="{{ (int) ($item->to_branch_id ?? 0) }}"
                        title="{{ __('message.follow_up_assign_rider') }}">
                    <span>{{ $deliveryRider !== '-' ? $deliveryRider : __('message.assign_rider') }}</span>
                    <i class="fas fa-user-edit" aria-hidden="true"></i>
                </button>
            @else
                <span class="pds-follow-up-cell pds-follow-up-cell--rider" title="{{ $deliveryRider }}">{{ $deliveryRider !== '-' ? $deliveryRider : '-' }}</span>
            @endif
        </td>
        <td class="pds-fu-col pds-fu-col--money pds-follow-up-num-cell">{{ (float) $item->advance_paid == 0.0 ? '' : number_format((float) $item->advance_paid) }}</td>
        <td class="pds-fu-col pds-fu-col--money pds-follow-up-num-cell">{{ number_format((float) $item->item_value) }}</td>
        <td class="pds-fu-col pds-fu-col--deli text-right pds-follow-up-num-cell">{!! formatDispatchDeliAmountHtml($item) !!}</td>
        <td class="pds-fu-col pds-fu-col--remark" title="{{ $item->remark }}">{{ stringLong($item->remark ?? '', 'title', 24) ?: '-' }}</td>
        <td class="pds-fu-col pds-fu-col--action pds-follow-up-actions-cell">
            <button type="button"
                    class="pds-follow-up-details-btn"
                    data-url="{{ route('order.dispatch.to-assign.details', $item->id) }}">
                <i class="fas fa-eye" aria-hidden="true"></i>
                <span>{{ __('message.details') }}</span>
            </button>
        </td>
    </tr>
@empty
@endforelse
