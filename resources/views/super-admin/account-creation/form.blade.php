@extends('super-admin.layout')

@php
    $isEdit = !empty($user);
    $title = $isEdit
        ? __('message.update_form_title', ['form' => __('message.sub_admin')])
        : __('message.add_form_title', ['form' => __('message.sub_admin')]);
@endphp

@section('title', $title)
@section('page_title', $title)

@section('content')
<div class="sa-module-page" style="padding-top: 0;">
    <a href="{{ route('super-admin.screens.show', ['screen' => 'account-creation']) }}" class="sa-module-page__back">
        <i class="fas fa-arrow-left"></i> {{ __('message.sa_screen_account_creation') }}
    </a>

    <div class="sa-module-panel sa-form-panel">
        <form method="POST"
              action="{{ $isEdit ? route('super-admin.account-creation.update', $user->id) : route('super-admin.account-creation.store') }}">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            <div class="sa-form-grid">
                <div class="sa-field">
                    <label>{{ __('message.role') }}</label>
                    <select name="user_type" required>
                        <option value="">{{ __('message.select_name', ['select' => __('message.role')]) }}</option>
                        @foreach(($roleOptions ?? []) as $value => $label)
                            <option value="{{ $value }}" @selected((string) old('user_type', $user->user_type ?? '') === (string) $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('user_type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="sa-field">
                    <label>{{ __('message.name') }}</label>
                    <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" required>
                    @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="sa-field">
                    <label>{{ __('message.email') }}</label>
                    <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required>
                    @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="sa-field">
                    <label>{{ __('message.username') }}</label>
                    <input type="text" name="username" value="{{ old('username', $user->username ?? '') }}" required>
                    @error('username')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="sa-field">
                    <label>{{ __('message.contact_number') }}</label>
                    <input type="text" name="contact_number" value="{{ old('contact_number', $user->contact_number ?? '') }}" required>
                    @error('contact_number')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="sa-field">
                    <label>{{ __('message.status') }}</label>
                    <select name="status">
                        <option value="1" @selected((string) old('status', $user->status ?? 1) === '1')>{{ __('message.active') }}</option>
                        <option value="0" @selected((string) old('status', $user->status ?? 1) === '0')>{{ __('message.inactive') }}</option>
                    </select>
                </div>
                <div class="sa-field">
                    <label>{{ __('message.password') }} {{ $isEdit ? __('message.sa_password_keep') : '' }}</label>
                    <div class="sa-input-wrap">
                        <input type="password" name="password" class="password" {{ $isEdit ? '' : 'required' }} autocomplete="new-password">
                        <i class="sa-toggle-password fas fa-eye-slash" role="button" tabindex="0" aria-label="Show password"></i>
                    </div>
                    @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="sa-field">
                    <label>{{ __('message.confirm_password') }}</label>
                    <div class="sa-input-wrap">
                        <input type="password" name="password_confirmation" class="password" {{ $isEdit ? '' : 'required' }} autocomplete="new-password">
                        <i class="sa-toggle-password fas fa-eye-slash" role="button" tabindex="0" aria-label="Show password"></i>
                    </div>
                </div>
            </div>

            <div class="sa-form-footer">
                <button type="submit" class="sa-btn sa-btn-primary">
                    {{ $isEdit ? __('message.sa_save_changes') : __('message.save') }}
                </button>
                <a href="{{ route('super-admin.screens.show', ['screen' => 'account-creation']) }}" class="sa-btn sa-btn-ghost">
                    {{ __('message.cancel') }}
                </a>
            </div>
        </form>
    </div>
</div>

<script>
    document.querySelectorAll('.sa-toggle-password').forEach(function (toggle) {
        function flip() {
            var input = toggle.closest('.sa-input-wrap').querySelector('.password');
            var isPassword = input.getAttribute('type') === 'password';
            input.setAttribute('type', isPassword ? 'text' : 'password');
            toggle.classList.toggle('fa-eye', isPassword);
            toggle.classList.toggle('fa-eye-slash', !isPassword);
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
