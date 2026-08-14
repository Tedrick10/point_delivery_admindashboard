@php
    $previewId = $previewId ?? 'profile_image_preview';
    $defaultImage = asset('images/user/1.jpg');
@endphp

<div class="pds-profile-upload">
    <div class="pds-profile-upload__ring">
        <img src="{{ $profileImage ?? $defaultImage }}"
             alt="{{ __('message.profile_image') }}"
             id="{{ $previewId }}"
             class="pds-profile-upload__img profile_image_preview">
        <label class="pds-profile-upload__camera" for="profile_image_input" title="{{ __('message.choose_profile_image') }}">
            <i class="fas fa-camera"></i>
        </label>
        <input type="file"
               accept="image/*"
               name="profile_image"
               id="profile_image_input"
               class="pds-profile-upload__input custom-file-input"
               data--target="{{ $previewId }}">
    </div>
    <p class="pds-profile-upload__title">{{ __('message.profile_image') }}</p>
    <p class="pds-profile-upload__hint">{{ __('message.choose_profile_image') }}</p>
</div>
