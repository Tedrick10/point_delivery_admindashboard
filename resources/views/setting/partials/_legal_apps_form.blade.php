@php
    $userSetting = $user_setting ?? null;
    $riderSetting = $rider_setting ?? null;
    $userValue = old('user_value', optional($userSetting)->value ?? '');
    $riderValue = old('rider_value', optional($riderSetting)->value ?? '');
    $userEditorClass = $userEditorClass ?? 'tinymce-legal-user';
    $riderEditorClass = $riderEditorClass ?? 'tinymce-legal-rider';
    $icon = $icon ?? 'fa-file-alt';
    $subtitle = $subtitle ?? '';
@endphp

<div class="container-fluid pds-page-wrap pds-motion-enter pds-legal-page" data-legal-editor=".{{ $userEditorClass }}, .{{ $riderEditorClass }}">
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

    {!! html()->form('POST', $saveRoute)->attribute('data-toggle', 'validator')->id('legal_page_form')->open() !!}
    @if($userSetting)
        {!! html()->hidden('user_id', $userSetting->id) !!}
    @endif
    @if($riderSetting)
        {!! html()->hidden('rider_id', $riderSetting->id) !!}
    @endif

    <div class="pds-legal-apps-grid">
        <section class="pds-legal-panel">
            <div class="pds-legal-panel__head">
                <span>{{ __('message.legal_tab_user_app') }} — Point User</span>
            </div>
            <div class="pds-legal-panel__body">
                <textarea name="user_value" class="form-control {{ $userEditorClass }}">{{ $userValue }}</textarea>
            </div>
        </section>

        <section class="pds-legal-panel">
            <div class="pds-legal-panel__head">
                <span>{{ __('message.legal_tab_rider_app') }} — Point Delivery Partner</span>
            </div>
            <div class="pds-legal-panel__body">
                <textarea name="rider_value" class="form-control {{ $riderEditorClass }}">{{ $riderValue }}</textarea>
            </div>
        </section>
    </div>

    <div class="pds-legal-footer">
        {!! html()->submit(__('message.save'))->class('btn btn-primary pds-legal-save') !!}
        <a class="btn btn-outline-secondary ms-2" href="{{ $previewUrl ?? '#' }}" target="_blank" rel="noopener">
            {{ __('message.legal_view_on_website') }}
        </a>
    </div>
    {!! html()->form()->close() !!}
</div>
