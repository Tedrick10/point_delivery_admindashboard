@php
    $orderId = $item->order_id;
    $order = $item->relationLoaded('order') ? $item->order : $item->order()->first();
    $canEditInfo = $order
        ? app(\App\Services\DispatchOrderWorkflowService::class)->canAdminEditDispatchItemInfo($order, $item)
        : false;
    $osName = $order ? resolveDispatchOsName($order) : '-';
    $osProfileImage = resolveUploadedProfileImageUrl(optional($order)->client);
    $unreadClientReplies = \App\Models\DispatchItemMessage::query()
        ->where('dispatch_order_item_id', $item->id)
        ->where('sender_type', 'client')
        ->whereNull('read_at')
        ->count();
    $metaLine = trim(
        ($item->code ? '#'.$item->code : '#'.$item->id)
        .' · '
        .($item->customer_phone ?: '')
    );
@endphp
<div class="pds-dispatch-row-actions">
    @if(!empty($showAssign100Print))
        <a href="{{ route('order.dispatch.assign-100-labels', ['ids' => [$item->id]]) }}"
           class="pds-dispatch-action-print"
           target="_blank"
           title="{{ __('message.assign_100_print_labels') }}">
            <i class="fas fa-qrcode"></i>
        </a>
    @endif
    @if(auth()->user()->can('order-edit'))
        <a href="javascript:void(0)"
           class="pds-dispatch-action-message js-dispatch-item-message"
           data-order-id="{{ $orderId }}"
           data-item-id="{{ $item->id }}"
           data-os-name="{{ e($osName !== '-' ? $osName : '') }}"
           data-os-profile-image="{{ e($osProfileImage ?? '') }}"
           data-customer="{{ e($item->customer_name ?? '') }}"
           data-meta="{{ e($metaLine) }}"
           data-list-url="{{ route('order.dispatch.item.messages', [$orderId, $item->id]) }}"
           data-store-url="{{ route('order.dispatch.item.messages.store', [$orderId, $item->id]) }}"
           title="{{ __('message.chat') }}">
            <i class="fab fa-facebook-messenger"></i>
            @if($unreadClientReplies > 0)
                <span class="pds-dispatch-msg-badge">{{ $unreadClientReplies }}</span>
            @endif
        </a>
    @endif
    @if(empty($hideGate) && auth()->user()->can('order-edit'))
        <a href="javascript:void(0)"
           class="pds-dispatch-action-gate js-dispatch-item-gate"
           data-url="{{ route('order.dispatch.item.update-gate', [$orderId, $item->id]) }}"
           data-customer="{{ e($item->customer_name ?? '') }}"
           data-gate-amount="{{ (float) ($item->gate_amount ?? 0) }}"
           data-gate-os="{{ (float) ($item->gate_os_paid ?? 0) }}"
           data-remark="{{ e($item->remark ?? '') }}"
           title="{{ __('message.update_gate') }}">
            <i class="fas fa-train"></i>
        </a>
    @endif
    @if(auth()->user()->can('order-edit') && $canEditInfo)
        <a href="{{ route('order.dispatch.item.edit', [$orderId, $item->id]) }}"
           class="pds-dispatch-action-edit loadRemoteModel"
           title="{{ __('message.edit') }}">
            <i class="fas fa-pen"></i>
        </a>
    @endif
    @if(auth()->user()->can('order-delete') && $canEditInfo)
        <a href="javascript:void(0)"
           class="pds-dispatch-action-delete"
           data-dispatch-item-delete="1"
           data-delete-url="{{ route('order.dispatch.item.destroy', [$orderId, $item->id]) }}"
           data-title="{{ __('message.delete_form_title', ['form' => __('message.item_name')]) }}"
           data-message="{{ __('message.delete_msg') }}"
           title="{{ __('message.delete') }}">
            <i class="fas fa-trash-alt"></i>
        </a>
    @endif
</div>
