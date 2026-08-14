@if($action_type == 'action')
<div class="d-flex align-items-center">
    {{-- View button --}}
    <a class="btn btn-sm btn-icon btn-warning me-2"href="{{ route('admin-login-device.show', $user_id) }}"data-bs-toggle="tooltip"title="{{ __('message.view_details') }}">
        <span class="btn-inner">
            <svg class="icon-20" width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" stroke="currentColor" d="M15.1614 12.0531C15.1614 13.7991 13.7454 15.2141 11.9994 15.2141C10.2534 15.2141 8.83838 13.7991 8.83838 12.0531C8.83838 10.3061 10.2534 8.89111 11.9994 8.89111C13.7454 8.89111 15.1614 10.3061 15.1614 12.0531Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                <path fill-rule="evenodd" clip-rule="evenodd" d="M11.998 19.355C15.806 19.355 19.289 16.617 21.25 12.053C19.289 7.48898 15.806 4.75098 11.998 4.75098H12.002C8.194 4.75098 4.711 7.48898 2.75 12.053C4.711 16.617 8.194 19.355 12.002 19.355H11.998Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
        </span>
    </a>

    {{-- Logout or Logged Out --}}
    <a href="{{ route('admin.device.logout', $id) }}"class="btn btn-sm btn-danger border-radius-10 ml-2"onclick="return confirm('{{ __('message.logout_confirm') }}')" data-bs-toggle="tooltip"title="{{ __('message.logout') }}">
        <i class="fa fa-sign-out"></i>
    </a>
</div>
@endif
