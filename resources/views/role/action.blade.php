@php
    $auth_user = authSession();
@endphp
@if( !in_array($role->name, ['user', 'agent', 'admin', 'client', 'delivery_man', 'demo_admin']) )
<div class="d-flex justify-content-center align-items-center">
    <div class="custom-control custom-switch custom-switch-text custom-switch-color custom-control-inline">
        <div class="custom-switch-inner">
            <input type="checkbox" class="custom-control-input bg-success change_status" data-type="role" id="{{ $role->id }}" data-id="{{ $role->id }}" {{ $role->status ? 'checked' : '' }} value = "{{ $role->id }}">
            <label class="custom-control-label" for="{{ $role->id }}" data-on-label="" data-off-label=""></label>
        </div>
    </div>
    @if($auth_user->can('role-add'))
        {{ html()->form('DELETE', route('role.destroy', $role->id))->attribute('data--submit', 'role' . $role->id)->open() }}
            <a class="ml-2 text-danger" href="javascript:void(0)" data--submit="role{{ $role->id }}"
                data--confirmation="true"
                data-title="{{ __('message.delete_form_title',['form'=> __('message.role') ]) }}"
                title="{{ __('message.delete_form_title',['form'=> __('message.role') ]) }}"
                data-message="{{ __('message.delete_msg') }}">
                <i class="fas fa-trash-alt"></i>
            </a>
        {{ html()->form()->close() }}
    @endif
</div>
@endif
