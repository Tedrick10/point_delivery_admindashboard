@php
    $fieldName = $fieldName ?? 'image';
    $previewId = $previewId ?? 'form_image_preview';
    $inputId = $inputId ?? $fieldName . '_input';
    $title = $title ?? __('message.image');
    $hint = $hint ?? __('message.choose_file', ['file' => __('message.image')]);
    $imageUrl = $imageUrl ?? asset('images/default.png');
    $showLabels = $showLabels ?? true;
    $frameClass = $frameClass ?? '';
@endphp

<div class="pds-vehicle-upload">
    <div class="pds-vehicle-upload__frame {{ $frameClass }}">
        <img src="{{ $imageUrl }}"
             alt="{{ $title }}"
             id="{{ $previewId }}"
             class="pds-vehicle-upload__img {{ $previewId }}">
        <label class="pds-vehicle-upload__camera" for="{{ $inputId }}" title="{{ $hint ?: __('message.image') }}">
            <i class="fas fa-camera"></i>
        </label>
        <input type="file"
               accept="image/*"
               name="{{ $fieldName }}"
               id="{{ $inputId }}"
               class="pds-vehicle-upload__input custom-file-input"
               data--target="{{ $previewId }}">
    </div>
    @if($showLabels)
        <p class="pds-vehicle-upload__title">{{ $title }}</p>
        <p class="pds-vehicle-upload__hint">{{ $hint }}</p>
    @endif
</div>
