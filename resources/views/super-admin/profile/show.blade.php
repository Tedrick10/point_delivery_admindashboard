@extends('super-admin.layout')

@section('title', __('message.my_profile'))
@section('page_title', __('message.my_profile'))
@section('page_sub', __('message.sa_super_admin'))

@section('content')
@php
    $initial = mb_strtoupper(mb_substr((string) ($user->name ?? 'S'), 0, 1));
@endphp
@if($errors->any())
    <div class="sa-home-alert" role="alert" style="margin-bottom: 1rem; background:#FEF2F2; color:#991B1B;">
        <span>{{ $errors->first() }}</span>
    </div>
@endif

<div class="sa-module-panel sa-form-panel sa-profile-panel">
    <header class="sa-module-panel__head">
        <div>
            <h3>{{ __('message.my_profile') }}</h3>
            <p class="text-muted mb-0">{{ __('message.sa_profile_hint') }}</p>
        </div>
    </header>

    <form method="POST"
          action="{{ route('super-admin.profile.update') }}"
          enctype="multipart/form-data"
          class="sa-profile-form"
          id="saProfileForm">
        @csrf
        @method('PUT')

        <div class="sa-profile-hero">
            <div class="sa-profile-avatar" id="saProfileAvatarPreview">
                @if(!empty($profileImage))
                    <img src="{{ $profileImage }}" alt="{{ $user->name }}" id="saProfileAvatarImg">
                @else
                    <span class="sa-profile-avatar__fallback" id="saProfileAvatarFallback">{{ $initial }}</span>
                @endif
            </div>
            <div class="sa-profile-hero__copy">
                <strong>{{ $user->name }}</strong>
                <span>{{ $user->email }}</span>
                <label class="sa-profile-upload">
                    <input type="file"
                           name="profile_image"
                           id="saProfileImageInput"
                           accept="image/jpeg,image/png,image/webp,image/gif"
                           hidden>
                    <span><i class="fas fa-camera" aria-hidden="true"></i> {{ __('message.choose_profile_image') }}</span>
                </label>
                @error('profile_image')
                    <div class="sa-field-error">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="sa-form-grid">
            <div class="sa-field">
                <label for="sa-profile-name">{{ __('message.name') }}</label>
                <input id="sa-profile-name"
                       type="text"
                       name="name"
                       value="{{ old('name', $user->name) }}"
                       required
                       autocomplete="name"
                       placeholder="{{ __('message.name') }}">
                @error('name')
                    <div class="sa-field-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="sa-field">
                <label for="sa-profile-email">{{ __('message.email') }}</label>
                <input id="sa-profile-email"
                       type="email"
                       value="{{ $user->email }}"
                       disabled
                       readonly>
            </div>

            <div class="sa-field">
                <label for="sa-profile-password">{{ __('message.password') }}</label>
                <input id="sa-profile-password"
                       type="password"
                       name="password"
                       autocomplete="new-password"
                       placeholder="••••••••"
                       minlength="8">
                <small class="sa-field-hint">{{ __('message.sa_profile_password_hint') }}</small>
                @error('password')
                    <div class="sa-field-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="sa-field">
                <label for="sa-profile-password-confirm">{{ __('message.confirm_password') }}</label>
                <input id="sa-profile-password-confirm"
                       type="password"
                       name="password_confirmation"
                       autocomplete="new-password"
                       placeholder="••••••••"
                       minlength="8">
            </div>
        </div>

        <div class="sa-form-footer">
            <button type="submit" class="sa-btn sa-btn--primary">
                <i class="fas fa-save" aria-hidden="true"></i>
                <span>{{ __('message.save') }}</span>
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var input = document.getElementById('saProfileImageInput');
    var box = document.getElementById('saProfileAvatarPreview');
    if (!input || !box) return;
    input.addEventListener('change', function () {
        var file = input.files && input.files[0];
        if (!file) return;
        var url = URL.createObjectURL(file);
        var img = document.getElementById('saProfileAvatarImg');
        var fallback = document.getElementById('saProfileAvatarFallback');
        if (!img) {
            img = document.createElement('img');
            img.id = 'saProfileAvatarImg';
            img.alt = 'Profile';
            box.innerHTML = '';
            box.appendChild(img);
        }
        img.src = url;
        if (fallback) fallback.remove();
    });
})();
</script>
@endpush
