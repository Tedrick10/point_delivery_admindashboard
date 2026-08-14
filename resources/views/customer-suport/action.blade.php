<?php
    $auth_user = authSession();
?>
<div class="pds-support-actions d-flex justify-content-center align-items-center gap-2">
    @if($auth_user->can('customersupport-show'))
        <a class="pds-support-action-btn pds-support-action-btn--chat" href="{{ route('customersupport.show', $id) }}" title="{{ __('message.customer_support') }}">
            <i class="fa-solid fa-message"></i>
        </a>
    @endif

    @if($auth_user->can('customersupport-delete'))
        {{ html()->form('DELETE', route('customersupport.destroy', $id))->attribute('data--submit', 'customer_support'.$id)->open() }}
            <a class="pds-support-action-btn pds-support-action-btn--delete"
               href="javascript:void(0)"
               data--submit="customer_support{{ $id }}"
               data--confirmation="true"
               data-title="{{ __('message.delete_form_title', ['form' => __('message.customer_support')]) }}"
               title="{{ __('message.delete_form_title', ['form' => __('message.customer_support')]) }}"
               data-message="{{ __('message.delete_msg') }}">
                <i class="fas fa-trash-alt"></i>
            </a>
        {{ html()->form()->close() }}
    @endif
</div>
