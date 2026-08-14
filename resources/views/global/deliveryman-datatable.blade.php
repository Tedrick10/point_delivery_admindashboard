{!! html()->form('GET', url()->current())->class('pds-list-filters')->open() !!}
    <div class="pds-list-filters__field">
        {!! html()->label(__('message.country'), 'country_id')->class('pds-list-filters__label') !!}
        {!! html()->select('country_id', $country, $selectedCountryId)
            ->class('select2js country')
            ->attribute('data-placeholder', __('message.select_name', ['select' => __('message.country')]))
            ->attribute('data-ajax--url', route('ajax-list', ['type' => 'country-list'])) !!}
    </div>

    <div class="pds-list-filters__field">
        {!! html()->label(__('message.city'), 'city_id')->class('pds-list-filters__label') !!}
        {!! html()->select('city_id', $cities, $selectedCityId)
            ->class('select2js city')
            ->attribute('data-placeholder', __('message.select_name', ['select' => __('message.city')]))
            ->attribute('data-ajax--url', route('ajax-list', ['type' => 'city-list'])) !!}
    </div>

    <div class="pds-list-filters__field">
        {!! html()->label(__('message.status'), 'last_actived_at')->class('pds-list-filters__label') !!}
        {!! html()->select('last_actived_at', [
            '' => __('message.all'),
            'active_user' => __('message.active_user'),
            'engaged_user' => __('message.engaged_user'),
            'inactive_user' => __('message.inactive_user'),
        ], $params['last_actived_at'] ?? old($params['last_actived_at'] ?? ''))
            ->class('select2js') !!}
    </div>

    <div class="pds-list-filters__actions">
        <button type="submit" class="btn btn-sm btn-primary">{{ __('message.apply_filter') }}</button>
        @if(isset($reset_file_button))
            @php
                $resetUrl = request()->url();
                if (request('status')) {
                    $resetUrl .= '?status=' . request('status');
                }
            @endphp
            <a href="{{ $resetUrl }}" class="btn btn-sm btn-outline-primary">
                <i class="ri-repeat-line"></i> {{ __('message.reset_filter') }}
            </a>
        @endif
    </div>
{!! html()->form()->close() !!}
