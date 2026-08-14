@php
    $previewId = 'vehicle_image_preview';
    $imageUrl = (isset($id) && getMediaFileExit($data, 'vehicle_image'))
        ? getSingleMedia($data, 'vehicle_image')
        : asset('images/default.png');
@endphp

<div class="pds-vehicle-upload">
    <div class="pds-vehicle-upload__frame">
        <img src="{{ $imageUrl }}"
             alt="{{ __('message.vehicle_image') }}"
             id="{{ $previewId }}"
             class="pds-vehicle-upload__img vehicle_image_preview">
        <label class="pds-vehicle-upload__camera" for="vehicle_image_input" title="{{ __('message.choose_file', ['file' => __('message.image')]) }}">
            <i class="fas fa-camera"></i>
        </label>
        <input type="file"
               accept="image/*"
               name="vehicle_image"
               id="vehicle_image_input"
               class="pds-vehicle-upload__input custom-file-input"
               data--target="{{ $previewId }}">
    </div>
    <p class="pds-vehicle-upload__title">{{ __('message.vehicle_image') }}</p>
    <p class="pds-vehicle-upload__hint">{{ __('message.choose_file', ['file' => __('message.image')]) }}</p>
</div>
