@php
    $sections = $sections ?? [];
    $saveRoute = $saveRoute ?? route('super-admin.system-settings.app-store-update');
    $forceOptions = [
        '0' => __('message.no'),
        '1' => __('message.yes'),
    ];
@endphp

<form method="POST" action="{{ $saveRoute }}" class="sa-settings-form sa-app-store-form">
    @csrf

    <div class="sa-settings-hero sa-settings-hero--quiet">
        <div class="sa-settings-hero__icon"><i class="fas fa-cloud-download-alt" aria-hidden="true"></i></div>
        <div>
            <h4 class="sa-settings-hero__title">{{ __('message.sa_screen_app_store_update') }}</h4>
            <p class="sa-settings-hero__text">{{ __('message.sa_app_store_help') }}</p>
        </div>
    </div>

    <div class="sa-app-store-grid">
        @foreach($sections as $appKey => $section)
            @php
                $type = $section['type'];
                $values = $section['values'] ?? [];
            @endphp
            <section class="sa-gs-block sa-app-store-card">
                <header class="sa-app-store-card__head">
                    <div class="sa-app-store-card__icon">
                        <i class="fas {{ $section['icon'] ?? 'fa-mobile-alt' }}" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h4>{{ __('message.'.$section['title_key']) }}</h4>
                        <p>{{ __('message.'.$section['subtitle_key']) }}</p>
                        <div class="sa-settings-chip-row">
                            <span class="sa-settings-chip">Android: {{ $section['package_android'] }}</span>
                            <span class="sa-settings-chip">iOS: {{ $section['package_ios'] }}</span>
                        </div>
                    </div>
                </header>

                <div class="sa-settings-grid">
                    <div class="sa-settings-field">
                        <label class="sa-settings-label" for="{{ $type }}_ANDROID_VERSION_CODE">
                            <i class="fab fa-android" aria-hidden="true"></i>
                            <span>{{ __('message.android_version_code') }}</span>
                        </label>
                        <input
                            type="number"
                            min="1"
                            step="1"
                            name="apps[{{ $type }}][ANDROID_VERSION_CODE]"
                            id="{{ $type }}_ANDROID_VERSION_CODE"
                            class="sa-settings-input"
                            value="{{ old('apps.'.$type.'.ANDROID_VERSION_CODE', $values['ANDROID_VERSION_CODE'] ?? '') }}"
                            placeholder="e.g. 54"
                        >
                        <p class="sa-settings-hint">{{ __('message.android_version_code_hint') }}</p>
                    </div>

                    <div class="sa-settings-field">
                        <label class="sa-settings-label" for="{{ $type }}_ANDROID_FORCE_UPDATE">
                            <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                            <span>{{ __('message.android_force_update') }}</span>
                        </label>
                        <select
                            name="apps[{{ $type }}][ANDROID_FORCE_UPDATE]"
                            id="{{ $type }}_ANDROID_FORCE_UPDATE"
                            class="sa-settings-input"
                        >
                            @foreach($forceOptions as $opt => $label)
                                <option value="{{ $opt }}" @selected((string) old('apps.'.$type.'.ANDROID_FORCE_UPDATE', $values['ANDROID_FORCE_UPDATE'] ?? '0') === (string) $opt)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sa-settings-field sa-settings-field--full">
                        <label class="sa-settings-label" for="{{ $type }}_PLAYSTORE_URL">
                            <i class="fab fa-google-play" aria-hidden="true"></i>
                            <span>{{ __('message.play_store_url') }}</span>
                        </label>
                        <input
                            type="url"
                            name="apps[{{ $type }}][PLAYSTORE_URL]"
                            id="{{ $type }}_PLAYSTORE_URL"
                            class="sa-settings-input"
                            value="{{ old('apps.'.$type.'.PLAYSTORE_URL', $values['PLAYSTORE_URL'] ?? '') }}"
                            placeholder="https://play.google.com/store/apps/details?id=..."
                            inputmode="url"
                        >
                    </div>

                    <div class="sa-settings-field">
                        <label class="sa-settings-label" for="{{ $type }}_IOS_VERSION">
                            <i class="fab fa-apple" aria-hidden="true"></i>
                            <span>{{ __('message.ios_version') }}</span>
                        </label>
                        <input
                            type="text"
                            name="apps[{{ $type }}][IOS_VERSION]"
                            id="{{ $type }}_IOS_VERSION"
                            class="sa-settings-input"
                            value="{{ old('apps.'.$type.'.IOS_VERSION', $values['IOS_VERSION'] ?? '') }}"
                            placeholder="e.g. 1.2.0"
                        >
                        <p class="sa-settings-hint">{{ __('message.ios_version_hint') }}</p>
                    </div>

                    <div class="sa-settings-field">
                        <label class="sa-settings-label" for="{{ $type }}_IOS_FORCE_UPDATE">
                            <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                            <span>{{ __('message.ios_force_update') }}</span>
                        </label>
                        <select
                            name="apps[{{ $type }}][IOS_FORCE_UPDATE]"
                            id="{{ $type }}_IOS_FORCE_UPDATE"
                            class="sa-settings-input"
                        >
                            @foreach($forceOptions as $opt => $label)
                                <option value="{{ $opt }}" @selected((string) old('apps.'.$type.'.IOS_FORCE_UPDATE', $values['IOS_FORCE_UPDATE'] ?? '0') === (string) $opt)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sa-settings-field sa-settings-field--full">
                        <label class="sa-settings-label" for="{{ $type }}_APPSTORE_URL">
                            <i class="fab fa-app-store-ios" aria-hidden="true"></i>
                            <span>{{ __('message.app_store_url') }}</span>
                        </label>
                        <input
                            type="url"
                            name="apps[{{ $type }}][APPSTORE_URL]"
                            id="{{ $type }}_APPSTORE_URL"
                            class="sa-settings-input"
                            value="{{ old('apps.'.$type.'.APPSTORE_URL', $values['APPSTORE_URL'] ?? '') }}"
                            placeholder="https://apps.apple.com/app/id..."
                            inputmode="url"
                        >
                    </div>
                </div>
            </section>
        @endforeach
    </div>

    <div class="sa-settings-actions">
        <button type="submit" class="sa-settings-save">
            <i class="fas fa-check" aria-hidden="true"></i>
            {{ __('message.save') }}
        </button>
    </div>
</form>
