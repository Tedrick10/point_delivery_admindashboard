<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ mighty_language_direction() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ appCopy('admin', 'title') }}</title>
        <link rel="shortcut icon" class="site_favicon_preview" href="{{ getSingleMedia(appSettingData('get'), 'site_favicon', null) }}" />

        <!-- Fonts -->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap">

        <!-- Styles -->
        <link rel="stylesheet" href="{{ public_asset_ver('css/backend.css') }}">
        @php
            $guestTheme = trim(
                public_css_inline('css/admin-dashboard-theme.css')."\n".
                public_css_inline('css/admin-themes.css')
            );
            $liveBrandHex = $themeColor ?? brandColorHex();
            $liveBrandRgb = $brandColorRgb ?? brandColorRgb();
            $liveFontFamily = $brandFontFamily ?? brandFontCssFamily();
        @endphp
        @if($guestTheme !== '')
            <style id="pds-admin-theme-inline">{!! $guestTheme !!}</style>
        @else
            <link rel="stylesheet" href="{{ public_asset_ver('css/admin-dashboard-theme.css') }}">
            <link rel="stylesheet" href="{{ public_asset_ver('css/admin-themes.css') }}">
        @endif
        <style id="pds-brand-live">
        :root, html, body.pds-admin {
            --site-color: {{ $liveBrandHex }} !important;
            --primary: {{ $liveBrandHex }} !important;
            --admin-primary: {{ $liveBrandHex }} !important;
            --pds-primary: {{ $liveBrandHex }} !important;
            --pds-accent: {{ $liveBrandHex }} !important;
            --brand-rgb: {{ $liveBrandRgb }} !important;
            --admin-primary-light: rgba({{ $liveBrandRgb }}, 0.12) !important;
            --admin-primary-soft: rgba({{ $liveBrandRgb }}, 0.08) !important;
            --admin-font: {{ $liveFontFamily }} !important;
        }
        body.pds-admin .btn-primary,
        body.pds-admin .btn-warning,
        body.pds-admin .bg-primary {
            background-color: {{ $liveBrandHex }} !important;
            border-color: {{ $liveBrandHex }} !important;
        }
        </style>
        <script>window.pdsBrandColor = @json($liveBrandHex);</script>

        @if(mighty_language_direction() == 'rtl')
        <link rel="stylesheet" href="{{ asset('css/rtl.css') }}">
        @endif
        
        @if(isset($assets) && in_array('phone', $assets))
            <link rel="stylesheet" href="{{ asset('vendor/intlTelInput/css/intlTelInput.css') }}">
        @endif
    </head>
    <body class="pds-admin pds-theme-{{ uiThemePackId() }}">

        <div class="wrapper">
            {{ $slot }}
        </div>
         @include('partials._scripts')
    </body>
    <script>
        @if(isset($assets) && in_array('phone', $assets))
            var input = document.querySelector("#phone"),
            errorMsg = document.querySelector("#error-msg"),
            validMsg = document.querySelector("#valid-msg");

            if(input) {
                var iti = window.intlTelInput(input, {
                    hiddenInput: "contact_number",
                    separateDialCode: true,
                    initialCountry: "mm",
                    utilsScript: "{{ asset('vendor/intlTelInput/js/utils.js') }}" // just for formatting/placeholders etc
                });

                input.addEventListener("countrychange", function() {
                  validate();
                });

                // // here, the index maps to the error code returned from getValidationError - see readme
                var errorMap = [ "Invalid number", "Invalid country code", "Too short", "Too long", "Invalid number"];
                //
                // // initialise plugin
                const phone = $('#phone');
                const err = $('#error-msg');
                const succ = $('#valid-msg');
                var reset = function() {
                    err.addClass('d-none');
                    succ.addClass('d-none');
                    validate();
                };

                // on blur: validate
                $(document).on('blur, keyup','#phone',function () {
                    reset();
                    var val = $(this).val();
                    if (val.match(/[^0-9\.\+.\s.]/g)) {
                        $(this).val(val.replace(/[^0-9\.\+.\s.]/g, ''));
                    }
                    if(val === ''){
                        $('[type="submit"]').removeClass('disabled').prop('disabled',false);
                    }
                });

                // on keyup / change flag: reset
                input.addEventListener('change', reset);
                input.addEventListener('keyup', reset);

                var errorCode = '';

                function validate() {
                    if (input.value.trim()) {
                        if (iti.isValidNumber()) {
                            succ.removeClass('d-none');
                            err.html('');
                            err.addClass('d-none');
                            $('[type="submit"]').removeClass('disabled').prop('disabled',false);
                        } else {
                            errorCode = iti.getValidationError();
                            err.html(errorMap[errorCode]);
                            err.removeClass('d-none');
                            phone.closest('.form-group').addClass('has-danger');
                            $('[type="submit"]').addClass('disabled').prop('disabled',true);
                        }
                    }
                }
            }
        @endif
    </script>
</html>
