<?php $auth_user = authSession(); ?>
<div class="pds-support-actions d-flex justify-content-center align-items-center">
    @if($auth_user->can('defaultkeyword-edit'))
        <a class="pds-support-action-btn loadRemoteModel"
           href="{{ route('defaultkeyword.edit', $id) }}"
           title="{{ __('message.update_form_title', ['form' => __('message.defaultkeyword')]) }}">
            <i class="fas fa-edit"></i>
        </a>
    @endif
</div>
