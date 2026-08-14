@php
    $nrcPrefix = $nrcPrefix ?? 'os_profile';
    $nrcId = $nrcId ?? 'os_nrc';
    $nrcRegion = $nrcRegion ?? '';
    $nrcTown = $nrcTown ?? '';
    $nrcType = $nrcType ?? '';
    $nrcNumber = $nrcNumber ?? '';
    $nrcPreview = $nrcPreview ?? '';
    $myanmarNrcData = $myanmarNrcData ?? json_decode(
        @file_get_contents(public_path('data/myanmar-nrc.json')) ?: '[]',
        true
    );
    $nrcStates = $myanmarNrcData['states'] ?? [];
    $nrcTypes = $myanmarNrcData['types'] ?? [
        ['label_mm' => 'နိုင်'],
        ['label_mm' => 'ဧည့်'],
        ['label_mm' => 'ပြု'],
        ['label_mm' => 'စ'],
    ];
@endphp

<div class="pds-mm-nrc-box"
     id="{{ $nrcId }}_box"
     data-nrc-prefix="{{ $nrcPrefix }}"
     data-nrc-id="{{ $nrcId }}"
     data-initial-region="{{ $nrcRegion }}"
     data-initial-town="{{ $nrcTown }}"
     data-initial-type="{{ $nrcType }}"
     data-initial-number="{{ $nrcNumber }}"
     data-placeholder-state="{{ __('message.nrc_placeholder_state') }}"
     data-placeholder-town="{{ __('message.nrc_placeholder_town') }}"
     data-placeholder-town-locked="{{ __('message.nrc_placeholder_town_locked') }}"
     data-placeholder-type="{{ __('message.nrc_placeholder_type') }}">
    <div class="pds-mm-nrc-head">
        <div class="pds-mm-nrc-head-left">
            <span class="pds-mm-nrc-icon"><i class="fas fa-id-card"></i></span>
            <div>
                <span class="pds-mm-nrc-label">{{ __('message.myanmar_nrc') }}</span>
                <span class="pds-mm-nrc-hint">{{ __('message.nrc_format_hint') }}</span>
            </div>
        </div>
        <output id="{{ $nrcId }}_preview"
                class="pds-mm-nrc-preview-chip is-empty"
                for="{{ $nrcId }}_region {{ $nrcId }}_town {{ $nrcId }}_type {{ $nrcId }}_number"
                data-empty-text="{{ __('message.nrc_preview_empty') }}">{{ $nrcPreview ?: __('message.nrc_preview_empty') }}</output>
    </div>

    <div class="pds-mm-nrc-stack">
        <div class="pds-mm-nrc-stack-item pds-mm-nrc-strip-field pds-mm-nrc-strip-state">
            <label class="pds-mm-nrc-field-label" for="{{ $nrcId }}_region">{{ __('message.nrc_state') }}</label>
            <select name="{{ $nrcPrefix }}[nrc_region]" id="{{ $nrcId }}_region" class="pds-mm-nrc-select pds-mm-nrc-select2" title="{{ __('message.nrc_state') }}" data-nrc-role="state">
                <option value=""></option>
                @foreach($nrcStates as $state)
                    <option value="{{ $state['code_mm'] }}"
                            data-state-code="{{ $state['code'] }}"
                            data-short="{{ $state['code_mm'] }}"
                            @selected($nrcRegion === $state['code_mm'])>
                        {{ $state['code_mm'] }} - {{ $state['name_mm'] }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="pds-mm-nrc-stack-item pds-mm-nrc-strip-field pds-mm-nrc-strip-town">
            <label class="pds-mm-nrc-field-label" for="{{ $nrcId }}_town">{{ __('message.nrc_township') }}</label>
            <select name="{{ $nrcPrefix }}[nrc_town]" id="{{ $nrcId }}_town" class="pds-mm-nrc-select pds-mm-nrc-select2" title="{{ __('message.nrc_township') }}" data-nrc-role="town">
                <option value=""></option>
            </select>
        </div>

        <div class="pds-mm-nrc-stack-item pds-mm-nrc-stack-row">
            <div class="pds-mm-nrc-strip-field pds-mm-nrc-strip-type">
                <label class="pds-mm-nrc-field-label" for="{{ $nrcId }}_type">{{ __('message.nrc_type') }}</label>
                <select name="{{ $nrcPrefix }}[nrc_type]" id="{{ $nrcId }}_type" class="pds-mm-nrc-select pds-mm-nrc-select2" title="{{ __('message.nrc_type') }}" data-nrc-role="type">
                    <option value=""></option>
                    @foreach($nrcTypes as $type)
                        <option value="{{ $type['label_mm'] }}"
                                data-short="{{ $type['label_mm'] }}"
                                @selected($nrcType === $type['label_mm'])>
                            {{ $type['label_mm'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="pds-mm-nrc-strip-field pds-mm-nrc-strip-number">
                <label class="pds-mm-nrc-field-label" for="{{ $nrcId }}_number">{{ __('message.nrc_number') }}</label>
                <div class="pds-mm-nrc-number-row">
                    <input type="text"
                           name="{{ $nrcPrefix }}[nrc_number]"
                           id="{{ $nrcId }}_number"
                           class="pds-mm-nrc-number"
                           maxlength="6"
                           inputmode="numeric"
                           pattern="[0-9]*"
                           placeholder="{{ __('message.nrc_placeholder_number') }}"
                           value="{{ $nrcNumber }}"
                           title="{{ __('message.nrc_number') }}"
                           autocomplete="off">
                    <span class="pds-mm-nrc-counter"><span id="{{ $nrcId }}_counter">{{ strlen($nrcNumber) }}</span>/6</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.PDS_MYANMAR_NRC_DATA = @json($myanmarNrcData);
</script>
