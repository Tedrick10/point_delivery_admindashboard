{!! html()->modelForm($settings, 'POST' , $saveRoute ?? route('settingsUpdates'))->attribute('enctype', 'multipart/form-data')->attribute('data-toggle', 'validator')->attribute('class', 'sa-settings-form sa-general-setting-form')->open() !!}
{!! html()->hidden('id',  null)->class('form-control') !!}
{!! html()->hidden('page', $page)->class('form-control') !!}
<input type="hidden" name="color" value="{{ brandColorHex($activeBrandColor ?? null) }}">

@php
    $isDemo = env('APP_DEMO') == true;
    $brandAssets = [
        [
            'key' => 'site_logo',
            'label' => __('message.logo'),
            'previewId' => 'site_logo_preview',
            'previewClass' => 'site_logo site_logo_preview',
            'width' => 72,
        ],
        [
            'key' => 'site_favicon',
            'label' => __('message.favicon'),
            'previewId' => 'site_favicon_preview',
            'previewClass' => 'site_favicon site_favicon_preview',
            'width' => 40,
        ],
    ];
    $brandColors = $brandColors ?? array_values(brandColorPacks());
    $brandFonts = $brandFonts ?? array_values(brandFontPacks());
    $activeBrandColor = $activeBrandColor ?? brandColorId();
    $activeBrandFont = $activeBrandFont ?? brandFontId();
@endphp

<section class="sa-gs-block">
    <header class="sa-gs-block__head">
        <h4>Branding</h4>
        <p>Logo · favicon</p>
    </header>
    <div class="sa-gs-brand-row">
        @foreach($brandAssets as $asset)
            <div class="sa-gs-brand-tile">
                <div class="sa-gs-brand-tile__preview">
                    <img
                        src="{{ getSingleMedia($settings, $asset['key']) }}"
                        width="{{ $asset['width'] }}"
                        id="{{ $asset['previewId'] }}"
                        alt="{{ $asset['label'] }}"
                        class="image {{ $asset['previewClass'] }}"
                    >
                    @if(getMediaFileExit($settings, $asset['key']))
                        <a class="sa-gs-brand-tile__remove remove-file"
                           href="{{ route('remove.file', ['id' => $settings->id, 'type' => $asset['key']]) }}"
                           data--submit="confirm_form"
                           data--confirmation="true"
                           data--ajax="true"
                           title='{{ __("message.remove_file_title" , ["name" =>  __("message.image") ]) }}'
                           data-title='{{ __("message.remove_file_title" , ["name" =>  __("message.image") ]) }}'
                           data-message='{{ __("message.remove_file_msg") }}'>
                            <i class="ri-close-circle-line"></i>
                        </a>
                    @endif
                </div>
                <div class="sa-gs-brand-tile__meta">
                    <span class="sa-gs-brand-tile__label">{{ $asset['label'] }}</span>
                    @if($isDemo)
                        <span class="sa-settings-hint">{{ __('message.demo_permission_denied') }}</span>
                    @else
                        <label class="sa-gs-upload-btn" for="{{ $asset['key'] }}">
                            <i class="fas fa-upload" aria-hidden="true"></i>
                            Upload
                        </label>
                        <input type="file"
                               name="{{ $asset['key'] }}"
                               id="{{ $asset['key'] }}"
                               class="sa-gs-upload-input"
                               accept="image/*"
                               lang="en">
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</section>

<section class="sa-gs-block">
    <header class="sa-gs-block__head">
        <h4>{{ __('message.site_name') }}</h4>
        <p>{{ __('message.sa_screen_ui_theme') }}</p>
    </header>
    <div class="sa-gs-fields">
        <div class="sa-gs-field sa-gs-field--full">
            {!! html()->label(__('message.site_name'))->for('site_name')->class('sa-settings-label') !!}
            {!! html()->text('site_name', null)->class('sa-settings-input')->placeholder(__('message.site_name')) !!}
        </div>
        <div class="sa-gs-field sa-gs-field--full">
            <div class="sa-gs-theme-banner">
                <div>
                    <span class="sa-settings-label">{{ __('message.sa_screen_ui_theme') }}</span>
                    <p class="sa-gs-theme-banner__name">
                        <strong>{{ uiThemePack()['name'] ?? 'Classic Point' }}</strong>
                        <code>{{ uiThemePackId() }}</code>
                    </p>
                    <p class="sa-settings-hint mb-0">{{ __('message.sa_ui_theme_managed_hint') }}</p>
                </div>
                <a href="{{ route('super-admin.screens.show', 'ui-theme') }}" class="sa-settings-chip sa-settings-chip--primary">
                    <i class="fas fa-swatchbook" aria-hidden="true"></i>
                    {{ __('message.sa_screen_ui_theme') }}
                </a>
            </div>
        </div>
    </div>
</section>

<section class="sa-gs-block">
    <header class="sa-gs-block__head">
        <h4>{{ __('message.sa_brand_color') }}</h4>
        <p>{{ __('message.sa_brand_applies_all') }}</p>
    </header>
    <div class="sa-gs-choice-grid">
        @foreach($brandColors as $color)
            <label class="sa-gs-choice {{ ($activeBrandColor === $color['id']) ? 'is-active' : '' }}">
                <input type="radio"
                       name="brand_color"
                       value="{{ $color['id'] }}"
                       {{ $activeBrandColor === $color['id'] ? 'checked' : '' }}>
                <span class="sa-gs-choice__swatch" style="background: {{ $color['hex'] }}"></span>
                <span class="sa-gs-choice__body">
                    <strong>{{ $color['name'] }}</strong>
                    <code>{{ $color['hex'] }}</code>
                </span>
            </label>
        @endforeach
    </div>
</section>

<section class="sa-gs-block">
    <header class="sa-gs-block__head">
        <h4>{{ __('message.sa_brand_font') }}</h4>
        <p>{{ __('message.sa_brand_font_size_hint') }}</p>
    </header>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Rubik:wght@400;500;600;700;800&family=Noto+Sans+Myanmar:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @font-face {
            font-family: 'Z17Strength';
            src: url('{{ asset('fonts/brand/Z17Strength-Regular.ttf') }}') format('truetype');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'Z17Strength';
            src: url('{{ asset('fonts/brand/Z17Strength-Bold.ttf') }}') format('truetype');
            font-weight: 600;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'Z17Strength';
            src: url('{{ asset('fonts/brand/Z17Strength-Bold.ttf') }}') format('truetype');
            font-weight: 700;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'Z17-Strength';
            src: url('{{ asset('fonts/brand/Z17Strength-Regular.ttf') }}') format('truetype');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'Z17-Strength';
            src: url('{{ asset('fonts/brand/Z17Strength-Bold.ttf') }}') format('truetype');
            font-weight: 600;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'Z17-Strength';
            src: url('{{ asset('fonts/brand/Z17Strength-Bold.ttf') }}') format('truetype');
            font-weight: 700;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'Z17 Strength';
            src: url('{{ asset('fonts/brand/Z17Strength-Regular.ttf') }}') format('truetype');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'Z17 Strength';
            src: url('{{ asset('fonts/brand/Z17Strength-Bold.ttf') }}') format('truetype');
            font-weight: 600;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'Z17 Strength';
            src: url('{{ asset('fonts/brand/Z17Strength-Bold.ttf') }}') format('truetype');
            font-weight: 700;
            font-style: normal;
            font-display: swap;
        }
        .sa-gs-choice--font .sa-gs-font-sample {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            min-width: 0;
            position: relative;
            z-index: 1;
            pointer-events: none;
        }
        .sa-gs-choice--font .sa-gs-font-sample__name {
            font-size: 0.88rem;
            font-weight: 700;
            color: #111;
            line-height: 1.2;
        }
        .sa-gs-choice--font .sa-gs-font-sample__en {
            font-size: 1.05rem;
            font-weight: 600;
            color: #1c1917;
            letter-spacing: -0.01em;
            line-height: 1.25;
        }
        .sa-gs-choice--font .sa-gs-font-sample__mm {
            font-size: 0.92rem;
            font-weight: 600;
            color: #57534e;
            line-height: 1.35;
        }
        .sa-gs-choice--font .sa-gs-font-sample__meta {
            font-size: 0.68rem;
            font-weight: 500;
            color: #a8a29e;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace !important;
        }
    </style>
    <div class="sa-gs-choice-grid sa-gs-choice-grid--fonts">
        @foreach($brandFonts as $font)
            <label class="sa-gs-choice sa-gs-choice--font {{ ($activeBrandFont === $font['id']) ? 'is-active' : '' }}">
                <input type="radio"
                       name="brand_font"
                       value="{{ $font['id'] }}"
                       {{ $activeBrandFont === $font['id'] ? 'checked' : '' }}>
                <span class="sa-gs-font-sample" style="font-family: {{ $font['family'] }} !important;">
                    <span class="sa-gs-font-sample__name" style="font-family: {{ $font['family'] }} !important; font-size: {{ round(0.88 * (float) ($font['size_scale'] ?? 1), 3) }}rem;">{{ $font['name'] }}</span>
                    <span class="sa-gs-font-sample__en" style="font-family: {{ $font['family'] }} !important; font-size: {{ round(1.05 * (float) ($font['size_scale'] ?? 1), 3) }}rem;">Aa Bb Cc · Point Delivery</span>
                    <span class="sa-gs-font-sample__mm" style="font-family: {{ $font['family'] }} !important; font-size: {{ round(0.92 * (float) ($font['size_scale'] ?? 1), 3) }}rem;">မင်္ဂလာပါ · ပို့ဆောင်ရေး</span>
                    <span class="sa-gs-font-sample__meta">{{ $font['id'] }}</span>
                </span>
            </label>
        @endforeach
    </div>
</section>

<div class="sa-settings-actions sa-gs-actions">
    {!! html()->submit(__('message.save'))->class('btn btn-md btn-primary sa-settings-save') !!}
</div>
{!! html()->form()->close() !!}

<script>
(function () {
    function getExtension(filename) {
        var parts = filename.split('.');
        return parts[parts.length - 1];
    }
    function isImage(filename) {
        switch (getExtension(filename).toLowerCase()) {
            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'gif':
            case 'ico':
            case 'webp':
            case 'svg':
                return true;
        }
        return false;
    }
    function readURL(input, className) {
        if (!(input.files && input.files[0])) return;
        if (!isImage(input.files[0].name)) {
            alert('Image should be png/PNG, jpg/JPG & jpeg/JPG.');
            input.value = '';
            return;
        }
        var reader = new FileReader();
        reader.onload = function (e) {
            document.querySelectorAll('img.' + className).forEach(function (img) {
                img.setAttribute('src', e.target.result);
            });
        };
        reader.readAsDataURL(input.files[0]);
    }

    function syncChoiceActive(input) {
        var name = input.getAttribute('name');
        document.querySelectorAll('input[name="' + name + '"]').forEach(function (el) {
            var label = el.closest('.sa-gs-choice');
            if (label) label.classList.toggle('is-active', el.checked);
        });
        if (name === 'brand_color' && input.checked) {
            var code = input.closest('.sa-gs-choice');
            var hex = code ? code.querySelector('.sa-gs-choice__body code') : null;
            var colorInput = document.querySelector('input[name="color"]');
            if (hex && colorInput) colorInput.value = hex.textContent.trim();
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var logo = document.getElementById('site_logo');
        var favicon = document.getElementById('site_favicon');
        if (logo) logo.addEventListener('change', function () { readURL(this, 'site_logo'); });
        if (favicon) favicon.addEventListener('change', function () { readURL(this, 'site_favicon'); });

        document.querySelectorAll('input[name="brand_color"], input[name="brand_font"]').forEach(function (input) {
            input.addEventListener('change', function () { syncChoiceActive(input); });
            input.addEventListener('click', function () { syncChoiceActive(input); });
        });
    });
})();
</script>
