<?php $auth_user = authSession(); ?>
<div class="pds-support-actions d-flex justify-content-center align-items-center">
    @if($auth_user->can('languagewithkeyword-edit'))
        <a class="pds-support-action-btn loadRemoteModel"
           href="{{ route('languagewithkeyword.edit', $id) }}"
           title="{{ __('message.update_form_title', ['form' => __('message.language_with_keyword')]) }}">
            <i class="fas fa-edit"></i>
        </a>
    @endif
</div>
