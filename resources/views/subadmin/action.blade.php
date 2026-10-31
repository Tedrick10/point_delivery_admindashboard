<?php
    $auth_user = authSession();
    $canEdit = $auth_user->can('subadmin-edit') || $auth_user->can('users-edit');
    $canDelete = $auth_user->can('subadmin-delete') || $auth_user->can('users-delete');
?>
@if($deleted_at != null)
    <div class="pds-subadmin-row-actions">
        @if($canEdit)
            <a class="pds-subadmin-row-action is-restore"
               href="{{ route('sub-admin.restore', ['id' => $id, 'type' => 'restore']) }}"
               data--confirmation--restore="true"
               title="{{ __('message.restore_title') }}">
                <i class="ri-refresh-line" aria-hidden="true"></i>
            </a>
        @endif
        @if($canDelete)
            {{ html()->form('DELETE', route('sub-admin.force.delete', ['id' => $id, 'type' => 'forcedelete']))->attribute('data--submit', 'subadmin' . $id)->open() }}
                <a class="pds-subadmin-row-action is-delete"
                   href="javascript:void(0)"
                   data--submit="subadmin{{ $id }}"
                   data--confirmation="true"
                   data--ajax="true"
                   data-method="DELETE"
                   data-title="{{ __('message.delete_form_title', ['form' => __('message.sub_admin')]) }}"
                   title="{{ __('message.force_delete_form_title', ['form' => __('message.sub_admin')]) }}"
                   data-message="{{ __('message.force_delete_msg') }}">
                    <i class="fas fa-trash-alt" aria-hidden="true"></i>
                </a>
            {{ html()->form()->close() }}
        @endif
    </div>
@else
    @if($action_type == 'status')
        <div class="custom-switch custom-switch-text custom-switch-color custom-control-inline m-0">
            <div class="custom-switch-inner">
                <input type="checkbox" class="custom-control-input bg-success change_status" data-type="user" id="{{ $data->id }}" data-id="{{ $data->id }}" {{ ($data->status == '0' ? 0 : 1) ? 'checked' : '' }} value="{{ $data->id }}">
                <label class="custom-control-label" for="{{ $data->id }}" data-on-label="" data-off-label=""></label>
            </div>
        </div>
    @endif
    @if($action_type == 'action')
        <div class="pds-subadmin-row-actions">
            @if($canEdit)
                <a class="pds-subadmin-row-action is-edit"
                   href="{{ route('sub-admin.edit', $id) }}"
                   title="{{ __('message.update_form_title', ['form' => __('message.sub_admin')]) }}">
                    <i class="fas fa-pen" aria-hidden="true"></i>
                </a>
            @endif

            @if($canDelete)
                {{ html()->form('DELETE', route('sub-admin.destroy', $id))->attribute('data--submit', 'subadmin' . $id)->open() }}
                    <a class="pds-subadmin-row-action is-delete"
                       href="javascript:void(0)"
                       data--submit="subadmin{{ $id }}"
                       data--confirmation="true"
                       data--ajax="true"
                       data-method="DELETE"
                       data-title="{{ __('message.delete_form_title', ['form' => __('message.sub_admin')]) }}"
                       title="{{ __('message.delete_form_title', ['form' => __('message.sub_admin')]) }}"
                       data-message="{{ __('message.delete_msg') }}">
                        <i class="fas fa-trash-alt" aria-hidden="true"></i>
                    </a>
                {{ html()->form()->close() }}
            @endif
        </div>
    @endif
@endif
