<?php
    $auth_user = authSession();
    $id = $order->id;
    $delete_at = $order->deleted_at;
    $workflow = app(\App\Services\DispatchOrderWorkflowService::class);
    $isPickupCancelled = $workflow->isPickupErrorCancelled($order);
    $isPrePickUp = request('dispatch_status') === 'pre_order' || $workflow->isPrePickUpOrder($order);
?>
<div class="pds-dispatch-row-actions">
    @if($delete_at == null)
        @if($isPrePickUp && $auth_user->can('order-edit'))
            <a href="javascript:void(0)"
               class="pds-dispatch-action-move-order-list js-move-pre-pickup-to-order-list"
               data-url="{{ route('order.dispatch.move-pre-pickup-to-order-list', $id) }}"
               data-order-id="{{ $id }}"
               title="{{ __('message.move_to_order_list') }}">
                {{ __('message.move_to_order_list') }}
            </a>
        @endif
        @if($isPickupCancelled && $auth_user->can('order-edit'))
            <a href="javascript:void(0)"
               class="pds-dispatch-action-restore js-restore-pickup-cancelled"
               data-url="{{ route('order.dispatch.restore-pickup-cancelled', $id) }}"
               data-order-id="{{ $id }}"
               title="{{ __('message.restore_pickup_cancelled') }}">
                {{ __('message.restore_pickup_cancelled') }}
            </a>
        @endif
        @if($auth_user->can('order-edit'))
            <a href="{{ route('order.dispatch.edit', $id) }}" class="pds-dispatch-action-edit" title="{{ __('message.edit') }}">
                <i class="fas fa-pen"></i>
            </a>
        @endif
        @if($auth_user->can('order-edit') && !in_array($order->status, ['cancelled', 'completed']))
            <a href="{{ route('cance.order', $id) }}" class="pds-dispatch-action-cancel loadRemoteModel" title="{{ __('message.cancel_order') }}">
                <i class="fas fa-ban"></i>
            </a>
        @endif
        @if($auth_user->can('order-delete'))
            {{ html()->form('DELETE', route('order.destroy', $id))->attribute('data--submit', 'order' . $id)->open() }}
                <a href="javascript:void(0)" class="pds-dispatch-action-delete" data--submit="order{{ $id }}"
                   data--confirmation="true"
                   data-title="{{ __('message.delete_form_title', ['form' => __('message.order')]) }}"
                   title="{{ __('message.delete_form_title', ['form' => __('message.order')]) }}"
                   data-message="{{ __('message.delete_msg') }}">
                    <i class="fas fa-trash-alt"></i>
                </a>
            {{ html()->form()->close() }}
        @endif
    @endif
</div>
