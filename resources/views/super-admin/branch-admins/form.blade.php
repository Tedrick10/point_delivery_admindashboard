@extends('super-admin.layout')

@section('title', $admin ? __('message.sa_edit_branch_admin') : __('message.sa_new_branch_admin'))
@section('page_title', $admin ? __('message.sa_edit_branch_admin') : __('message.sa_new_branch_admin'))
@section('page_sub', __('message.sa_one_active_admin'))

@section('content')
<div class="sa-module-panel sa-form-panel">
    <form method="POST" action="{{ $admin ? route('super-admin.branch-admins.update', $admin->id) : route('super-admin.branch-admins.store') }}">
        @csrf
        @if($admin)
            @method('PUT')
        @endif

        <div class="sa-form-grid">
            <div class="sa-field">
                <label for="sa-admin-name">{{ __('message.name') }}</label>
                <input id="sa-admin-name" type="text" name="name" value="{{ old('name', $admin->name ?? '') }}" required autocomplete="name" placeholder="{{ __('message.name') }}">
            </div>
            <div class="sa-field">
                <label for="sa-admin-username">{{ __('message.username') }}</label>
                <input id="sa-admin-username" type="text" name="username" value="{{ old('username', $admin->username ?? '') }}" autocomplete="username" placeholder="{{ __('message.username') }}">
            </div>
            <div class="sa-field">
                <label for="sa-admin-email">{{ __('message.email') }}</label>
                <input id="sa-admin-email" type="email" name="email" value="{{ old('email', $admin->email ?? '') }}" required autocomplete="email" placeholder="name@admin.com">
            </div>
            <div class="sa-field">
                <label for="sa-admin-phone">{{ __('message.phone') }}</label>
                <input id="sa-admin-phone" type="text" name="contact_number" value="{{ old('contact_number', $admin->contact_number ?? '') }}" autocomplete="tel" placeholder="09">
            </div>
            <div class="sa-field">
                <label for="sa-admin-branch">{{ __('message.branch') }}</label>
                <select id="sa-admin-branch" name="branch_id" required>
                    <option value="">{{ __('message.sa_select_branch') }}</option>
                    @foreach(($branches ?? []) as $branch)
                        <option value="{{ $branch->id }}" @selected((int) old('branch_id', $prefillBranchId ?? 0) === (int) $branch->id)>
                            {{ $branch->displayLabel() }}
                        </option>
                    @endforeach
                </select>
                @error('branch_id')
                    <div class="sa-field-error">{{ $message }}</div>
                @enderror
            </div>
            <div class="sa-field">
                <label for="sa-admin-status">{{ __('message.status') }}</label>
                <select id="sa-admin-status" name="status">
                    <option value="1" @selected((string) old('status', $admin->status ?? 1) === '1')>{{ __('message.active') }}</option>
                    <option value="0" @selected((string) old('status', $admin->status ?? 1) === '0')>{{ __('message.inactive') }}</option>
                </select>
            </div>
            <div class="sa-field">
                <label for="sa-admin-password">{{ __('message.password') }} {{ $admin ? __('message.sa_password_keep') : '' }}</label>
                <div class="sa-input-wrap">
                    <input id="sa-admin-password" type="password" name="password" class="password" {{ $admin ? '' : 'required' }} autocomplete="new-password">
                    <i class="sa-toggle-password fas fa-eye-slash" role="button" tabindex="0" aria-label="Show password"></i>
                </div>
            </div>
            <div class="sa-field">
                <label for="sa-admin-password2">{{ __('message.confirm_password') }}</label>
                <div class="sa-input-wrap">
                    <input id="sa-admin-password2" type="password" name="password_confirmation" class="password" {{ $admin ? '' : 'required' }} autocomplete="new-password">
                    <i class="sa-toggle-password fas fa-eye-slash" role="button" tabindex="0" aria-label="Show password"></i>
                </div>
            </div>
        </div>

        <div class="sa-form-footer">
            <button type="submit" class="sa-btn sa-btn-primary">{{ $admin ? __('message.sa_save_changes') : __('message.sa_create_admin') }}</button>
            <a href="{{ route('super-admin.branch-admins.index') }}" class="sa-btn sa-btn-ghost">{{ __('message.cancel') }}</a>
        </div>
    </form>
</div>

<script>
    document.querySelectorAll('.sa-toggle-password').forEach(function (toggle) {
        function flip() {
            var input = toggle.closest('.sa-input-wrap').querySelector('.password');
            var isPassword = input.getAttribute('type') === 'password';
            input.setAttribute('type', isPassword ? 'text' : 'password');
            toggle.classList.toggle('fa-eye-slash', !isPassword);
            toggle.classList.toggle('fa-eye', isPassword);
            toggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
        }
        toggle.addEventListener('click', flip);
        toggle.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                flip();
            }
        });
    });
</script>
@endsection
