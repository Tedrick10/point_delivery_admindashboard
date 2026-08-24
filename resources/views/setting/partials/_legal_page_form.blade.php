@php
    $legalValue = old('value', optional($setting_data ?? null)->value ?? '');
    $editorClass = $editorClass ?? 'tinymce-legal';
    $icon = $icon ?? 'fa-file-alt';
    $subtitle = $subtitle ?? '';
@endphp

<div class="container-fluid pds-page-wrap pds-motion-enter pds-legal-page" data-legal-editor=".{{ $editorClass }}">
    <div class="pds-legal-hero">
        <div class="pds-legal-hero__icon">
            <i class="fas {{ $icon }}"></i>
        </div>
        <div class="pds-legal-hero__copy">
            <h4 class="pds-legal-hero__title">{{ $pageTitle }}</h4>
            @if($subtitle !== '')
                <p class="pds-legal-hero__subtitle">{{ $subtitle }}</p>
            @endif
        </div>
    </div>

    @if($setting_data)
        {!! html()->modelForm($setting_data, 'POST', $saveRoute)->attribute('data-toggle', 'validator')->id('legal_page_form')->open() !!}
        {!! html()->hidden('id') !!}
    @else
        {!! html()->form('POST', $saveRoute)->attribute('data-toggle', 'validator')->id('legal_page_form')->open() !!}
    @endif

    <div class="pds-legal-grid">
        <section class="pds-legal-panel">
            <div class="pds-legal-panel__head">
                <span>{{ __('message.legal_editor') }}</span>
            </div>
            <div class="pds-legal-panel__body">
                <textarea name="value" class="form-control {{ $editorClass }}">{{ $legalValue }}</textarea>
            </div>
        </section>

        <section class="pds-legal-panel pds-legal-panel--preview">
            <div class="pds-legal-panel__head">
                <span>{{ __('message.legal_live_preview') }}</span>
                <small>{{ __('message.legal_preview_hint') }}</small>
            </div>
            <div class="pds-legal-preview">
                <div class="pds-legal-preview__phone">
                    <div class="pds-legal-preview__notch"></div>
                    <div class="pds-legal-preview__bar">{{ $pageTitle }}</div>
                    <div class="pds-legal-article pds-legal-preview-body">
                        {!! $legalValue !!}
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="pds-legal-footer">
        {!! html()->submit(__('message.save'))->class('btn btn-primary pds-legal-save') !!}
    </div>
    {!! html()->form()->close() !!}
</div>
