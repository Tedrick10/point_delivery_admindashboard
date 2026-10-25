<?php
    $auth_user = authSession();
?>
@if($deleted_at != null)
    <div class="d-flex justify-content-end align-items-center">
        @if($auth_user->can('users-edit'))
            {!! html()->a(route('users.restore', ['id' => $id, 'type' => 'restore']))
                ->class('mr-2')
                ->attribute('data--confirmation--restore', 'true')
                ->attribute('title', __('message.restore_title'))
                ->html('<i class="ri-refresh-line" style="font-size:18px"></i>') !!}
        @endif

        {!! html()->form('DELETE', route('users.force.delete', ['id' => $id, 'type' => 'forcedelete']))->attribute('data--submit', 'users' . $id)->attribute('data-spa-ignore', '1')->open() !!}
            @if($auth_user->can('users-delete'))
                <a href="javascript:void(0)"
                   class="mr-2 text-danger"
                   data--submit="users{{ $id }}"
                   data--confirmation="true"
                   data--ajax="true"
                   data-method="DELETE"
                   data-spa-ignore="1"
                   data-title="{{ __('message.delete_form_title', ['form' => __('message.online_shop')]) }}"
                   title="{{ __('message.force_delete_form_title', ['form' => __('message.online_shop')]) }}"
                   data-message="{{ __('message.force_delete_msg') }}">
                    <i class="ri-delete-bin-2-fill" style="font-size:18px"></i>
                </a>
            @endif
        {!! html()->form()->close() !!}
    </div>
@else
    @if(in_array($action_type, ['email_verified', 'mobile_verified'], true))
        <div class="custom-switch custom-switch-text custom-switch-color custom-control-inline">
            <div class="custom-switch-inner">
                @if($action_type == 'email_verified')
                    {!! html()->checkbox('email_verified', $data['is_autoverified_email'] == '1', $data['id'])
                        ->class('custom-control-input bg-success change_user_verification')
                        ->id('email_' . $data['id'])
                        ->attribute('data-type', 'user')
                        ->attribute('data-name', 'is_autoverified_email')
                        ->attribute('data-id', $data['id']) !!}
                    {!! html()->label()->for('email_' . $data['id'])->class('custom-control-label') !!}
                @endif

                @if($action_type == 'mobile_verified')
                    {!! html()->checkbox('mobile_verified', $data['is_autoverified_mobile'] == '1', $data['id'])
                        ->class('custom-control-input bg-success change_user_verification')
                        ->id('mobile_' . $data['id'])
                        ->attribute('data-type', 'user')
                        ->attribute('data-name', 'is_autoverified_mobile')
                        ->attribute('data-id', $data['id']) !!}
                    {!! html()->label()->for('mobile_' . $data['id'])->class('custom-control-label') !!}
                @endif
            </div>
        </div>
    @endif

    @if($action_type == 'status')
        <div class="custom-switch custom-switch-text custom-switch-color custom-control-inline m-0">
            <div class="custom-switch-inner">
                {!! html()->checkbox('status', $data->status == '1', $data->id)
                    ->class('custom-control-input bg-success change_status')
                    ->id($data->id)
                    ->attribute('data-type', 'user')
                    ->attribute('data-id', $data->id) !!}
                {!! html()->label()->for($data->id)->class('custom-control-label')->attribute('data-on-label', 'On')->attribute('data-off-label', 'Off') !!}
            </div>
        </div>
    @endif

    @if($action_type == 'approval_status')
        @php
            $current = $data->approval_status ?? 'approved';
            if (! in_array($current, ['pending', 'approved', 'rejected'], true)) {
                $current = 'pending';
            }
        @endphp
        @if($auth_user->can('users-edit'))
            {{-- Rejected can always be changed back to Pending / Approved. --}}
            <select
                class="pds-os-approval-select js-os-approval-status"
                data-id="{{ $data->id }}"
                data-url="{{ route('users.approval-status', $data->id) }}"
                data-tone="{{ $current }}"
                data-prev="{{ $current }}"
                title="{{ __('message.approval_status') }} — {{ __('message.pending') }} / {{ __('message.approved') }} / {{ __('message.rejected') }}"
                aria-label="{{ __('message.approval_status') }}"
            >
                <option value="pending" @selected($current === 'pending')>{{ __('message.pending') }}</option>
                <option value="approved" @selected($current === 'approved')>{{ __('message.approved') }}</option>
                <option value="rejected" @selected($current === 'rejected')>{{ __('message.rejected') }}</option>
            </select>
        @else
            <span class="pds-os-approval-pill is-{{ $current }}">{{ __('message.'.$current) }}</span>
        @endif
    @endif

    @if($action_type == 'action')
        <div class="pds-os-row-actions">
            @if($auth_user->can('users-edit'))
                {!! html()->a(route('users.edit', $id))->class('pds-os-row-action is-edit')
                    ->attribute('title', __('message.update_form_title', ['form' => __('message.online_shop')]))
                    ->html('<i class="fas fa-pen"></i>') !!}
            @endif

            @if($auth_user->can('users-delete'))
                {!! html()->form('DELETE', route('users.destroy', $id))->attribute('data--submit', 'users' . $id)->class('d-inline')->attribute('data-spa-ignore', '1')->open() !!}
                    <a href="javascript:void(0)"
                       class="pds-os-row-action is-delete"
                       data--submit="users{{ $id }}"
                       data--confirmation="true"
                       data--ajax="true"
                       data-method="DELETE"
                       data-spa-ignore="1"
                       data-title="{{ __('message.delete_form_title', ['form' => __('message.online_shop')]) }}"
                       title="{{ __('message.delete_form_title', ['form' => __('message.online_shop')]) }}"
                       data-message="{{ __('message.delete_msg') }}">
                        <i class="fas fa-trash-alt"></i>
                    </a>
                {!! html()->form()->close() !!}
            @endif
        </div>
    @endif
@endif
