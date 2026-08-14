{!! html()->form('POST', route('settingUpdate'))->attribute('data-toggle', 'validator')->open() !!}
{!! html()->hidden('page', $page)->class('form-control') !!}

<div class="row">
    <div class="col-md-12">
        <div class="card shadow mb-3">
            <div class="card-header">
                <h4 class="mb-0">{{ __('message.api_server_settings') }}</h4>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">
                    {{ __('message.api_server_settings_help') }}
                </p>

                <div class="form-group">
                    <label for="API_SERVER_BASE_URL">{{ __('message.api_base_url') }}</label>
                    {!! html()->hidden('type[]', 'API_SERVER') !!}
                    <input type="hidden" name="key[]" value="API_SERVER_BASE_URL">
                    <div class="input-group">
                        <input type="text"
                               name="value[]"
                               id="API_SERVER_BASE_URL"
                               class="form-control form-control-lg"
                               value="{{ $apiBaseUrl }}"
                               placeholder="http://192.168.1.10:8000"
                               autocomplete="off"
                               inputmode="url">
                        <div class="input-group-append">
                            @if(!empty($wifiUrl))
                                <button type="button"
                                        class="btn btn-outline-primary"
                                        id="useWifiApiUrlBtn"
                                        title="{{ __('message.api_server_use_wifi') }}">
                                    {{ __('message.api_server_use_wifi') }}
                                </button>
                            @endif
                            <button type="button"
                                    class="btn btn-outline-secondary"
                                    id="useDetectedApiUrlBtn"
                                    title="{{ __('message.api_server_use_current') }}">
                                {{ __('message.api_server_use_current') }}
                            </button>
                        </div>
                    </div>
                    <small class="form-text text-muted d-block mt-2">
                        <strong>{{ __('message.api_server_wifi_ip') }}:</strong>
                        @if(!empty($wifiIp))
                            <code>{{ $wifiIp }}</code>
                            → <code>{{ $wifiUrl }}</code>
                        @else
                            <span class="text-danger">{{ __('message.api_server_wifi_not_found') }}</span>
                        @endif
                    </small>
                    <small class="form-text text-muted d-block">
                        {{ __('message.api_server_example') }}:
                        <code>{{ $browserUrl ?? $detectedUrl }}</code>
                    </small>
                    <small class="form-text text-muted d-block">
                        {{ __('message.api_server_wifi_help') }}
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

{!! html()->submit(__('message.save'))->class('btn btn-md btn-primary float-right') !!}
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
