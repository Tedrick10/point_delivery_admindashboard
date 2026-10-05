@php
    $sections = $appStoreUpdate['sections'] ?? [];
    $saveRoute = $appStoreUpdate['saveRoute'] ?? route('super-admin.system-settings.app-store-update');
@endphp

<div class="sa-asu" data-sa-asu>
    <form method="POST" action="{{ $saveRoute }}" class="sa-asu-form">
        @csrf

        <div class="sa-asu-grid">
            @foreach($sections as $appKey => $section)
                @php
                    $type = $section['type'];
                    $values = $section['values'] ?? [];
                    $androidForce = (string) old('apps.'.$type.'.ANDROID_FORCE_UPDATE', $values['ANDROID_FORCE_UPDATE'] ?? '0');
                    $iosForce = (string) old('apps.'.$type.'.IOS_FORCE_UPDATE', $values['IOS_FORCE_UPDATE'] ?? '0');
                @endphp
                <section class="sa-asu-card">
                    <header class="sa-asu-card__head">
                        <span class="sa-asu-card__icon" aria-hidden="true">
                            <i class="fas {{ $section['icon'] ?? 'fa-mobile-alt' }}"></i>
                        </span>
                        <div class="sa-asu-card__copy">
                            <strong>{{ __('message.'.$section['title_key']) }}</strong>
                            <small>{{ __('message.'.$section['subtitle_key']) }}</small>
                        </div>
                    </header>

                    <div class="sa-asu-ids">
                        <span><i class="fab fa-android" aria-hidden="true"></i> {{ $section['package_android'] }}</span>
                        <span><i class="fab fa-apple" aria-hidden="true"></i> {{ $section['package_ios'] }}</span>
                    </div>

                    <div class="sa-asu-platform">
                        <div class="sa-asu-platform__title">
                            <i class="fab fa-google-play" aria-hidden="true"></i>
                            Android
                        </div>
                        <label class="sa-asu-field">
                            <span>{{ __('message.play_store_url') }}</span>
                            <input type="url"
                                   name="apps[{{ $type }}][PLAYSTORE_URL]"
                                   value="{{ old('apps.'.$type.'.PLAYSTORE_URL', $values['PLAYSTORE_URL'] ?? '') }}"
                                   placeholder="https://play.google.com/store/apps/details?id=..."
                                   inputmode="url">
                        </label>
                        <div class="sa-asu-force">
                            <span>{{ __('message.android_force_update') }}</span>
                            <div class="sa-asu-toggle" role="group" aria-label="{{ __('message.android_force_update') }}">
                                <label class="{{ $androidForce !== '1' ? 'is-active' : '' }}">
                                    <input type="radio" name="apps[{{ $type }}][ANDROID_FORCE_UPDATE]" value="0" @checked($androidForce !== '1')>
                                    {{ __('message.no') }}
                                </label>
                                <label class="{{ $androidForce === '1' ? 'is-active' : '' }}">
                                    <input type="radio" name="apps[{{ $type }}][ANDROID_FORCE_UPDATE]" value="1" @checked($androidForce === '1')>
                                    {{ __('message.yes') }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="sa-asu-platform">
                        <div class="sa-asu-platform__title">
                            <i class="fab fa-app-store" aria-hidden="true"></i>
                            iOS
                        </div>
                        <label class="sa-asu-field">
                            <span>{{ __('message.app_store_url') }}</span>
                            <input type="url"
                                   name="apps[{{ $type }}][APPSTORE_URL]"
                                   value="{{ old('apps.'.$type.'.APPSTORE_URL', $values['APPSTORE_URL'] ?? '') }}"
                                   placeholder="https://apps.apple.com/app/id..."
                                   inputmode="url">
                        </label>
                        <div class="sa-asu-force">
                            <span>{{ __('message.ios_force_update') }}</span>
                            <div class="sa-asu-toggle" role="group" aria-label="{{ __('message.ios_force_update') }}">
                                <label class="{{ $iosForce !== '1' ? 'is-active' : '' }}">
                                    <input type="radio" name="apps[{{ $type }}][IOS_FORCE_UPDATE]" value="0" @checked($iosForce !== '1')>
                                    {{ __('message.no') }}
                                </label>
                                <label class="{{ $iosForce === '1' ? 'is-active' : '' }}">
                                    <input type="radio" name="apps[{{ $type }}][IOS_FORCE_UPDATE]" value="1" @checked($iosForce === '1')>
                                    {{ __('message.yes') }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <details class="sa-asu-advanced">
                        <summary>{{ __('message.sa_app_store_advanced') }}</summary>
                        <div class="sa-asu-advanced__body">
                            <label class="sa-asu-field">
                                <span>{{ __('message.android_version_code') }}</span>
                                <input type="number"
                                       min="1"
                                       step="1"
                                       name="apps[{{ $type }}][ANDROID_VERSION_CODE]"
                                       value="{{ old('apps.'.$type.'.ANDROID_VERSION_CODE', $values['ANDROID_VERSION_CODE'] ?? '') }}"
                                       placeholder="e.g. 54">
                                <small>{{ __('message.android_version_code_hint') }}</small>
                            </label>
                            <label class="sa-asu-field">
                                <span>{{ __('message.ios_version') }}</span>
                                <input type="text"
                                       name="apps[{{ $type }}][IOS_VERSION]"
                                       value="{{ old('apps.'.$type.'.IOS_VERSION', $values['IOS_VERSION'] ?? '') }}"
                                       placeholder="e.g. 1.2.0">
                                <small>{{ __('message.ios_version_hint') }}</small>
                            </label>
                        </div>
                    </details>
                </section>
            @endforeach
        </div>

        <div class="sa-asu-footer">
            <button type="submit" class="sa-btn sa-btn-primary">{{ __('message.save') }}</button>
        </div>
    </form>
</div>
<script>
(function () {
    var root = document.querySelector('[data-sa-asu]');
    if (!root) return;
    root.querySelectorAll('.sa-asu-toggle').forEach(function (group) {
        group.addEventListener('change', function (e) {
            if (!e.target || e.target.type !== 'radio') return;
            group.querySelectorAll('label').forEach(function (label) {
                label.classList.toggle('is-active', label.contains(e.target));
            });
        });
    });
})();
</script>
