{!! html()->form('POST', $saveRoute ?? route('settingUpdate'))->attribute('data-toggle', 'validator')->attribute('class', 'sa-settings-form sa-api-server-form')->open() !!}
{!! html()->hidden('page', $page)->class('form-control') !!}

<section class="sa-gs-block">
    <div class="sa-settings-hero sa-settings-hero--quiet">
        <div class="sa-settings-hero__icon"><i class="fas fa-network-wired" aria-hidden="true"></i></div>
        <div>
            <h4 class="sa-settings-hero__title">{{ __('message.api_server_settings') }}</h4>
            <p class="sa-settings-hero__text">{{ __('message.api_server_settings_help') }}</p>
        </div>
    </div>

    <div class="sa-settings-field sa-settings-field--full">
        <label class="sa-settings-label" for="API_SERVER_BASE_URL">
            <i class="fas fa-link" aria-hidden="true"></i>
            <span>{{ __('message.api_base_url') }}</span>
        </label>
        {!! html()->hidden('type[]', 'API_SERVER') !!}
        <input type="hidden" name="key[]" value="API_SERVER_BASE_URL">
        <input type="text"
               name="value[]"
               id="API_SERVER_BASE_URL"
               class="sa-settings-input sa-settings-input--lg"
               value="{{ $apiBaseUrl }}"
               placeholder="http://192.168.1.10:8000"
               autocomplete="off"
               inputmode="url">
        <div class="sa-settings-chip-row">
            @if(!empty($wifiUrl))
                <button type="button" class="sa-settings-chip sa-settings-chip--primary" id="useWifiApiUrlBtn">
                    <i class="fas fa-wifi" aria-hidden="true"></i>
                    {{ __('message.api_server_use_wifi') }}
                </button>
            @endif
            <button type="button" class="sa-settings-chip" id="useDetectedApiUrlBtn">
                <i class="fas fa-desktop" aria-hidden="true"></i>
                {{ __('message.api_server_use_current') }}
            </button>
        </div>
    </div>

    <div class="sa-settings-info-grid">
        <div class="sa-settings-info">
            <span class="sa-settings-info__label">{{ __('message.api_server_wifi_ip') }}</span>
            @if(!empty($wifiIp))
                <code>{{ $wifiIp }}</code>
                <code class="sa-settings-info__url">{{ $wifiUrl }}</code>
            @else
                <span class="sa-settings-info__warn">{{ __('message.api_server_wifi_not_found') }}</span>
            @endif
        </div>
        <div class="sa-settings-info">
            <span class="sa-settings-info__label">{{ __('message.api_server_example') }}</span>
            <code class="sa-settings-info__url">{{ $browserUrl ?? $detectedUrl }}</code>
            <p class="sa-settings-hint mb-0">{{ __('message.api_server_wifi_help') }}</p>
        </div>
    </div>

    <div class="sa-settings-actions">
        {!! html()->submit(__('message.save'))->class('sa-settings-save') !!}
    </div>
</section>
{!! html()->form()->close() !!}

<script>
    (function () {
        var wifiUrl = @json($wifiUrl ?? '');
        var detected = @json($detectedUrl);
        var browserUrl = @json($browserUrl ?? $detectedUrl);

        $('#useWifiApiUrlBtn').on('click', function () {
            if (wifiUrl) {
                $('#API_SERVER_BASE_URL').val(wifiUrl);
            }
        });

        $('#useDetectedApiUrlBtn').on('click', function () {
            $('#API_SERVER_BASE_URL').val(wifiUrl || detected || browserUrl);
        });
    })();
</script>
