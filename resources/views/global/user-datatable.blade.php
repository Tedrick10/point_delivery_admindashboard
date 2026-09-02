{!! html()->form('GET', url()->current())->class('pds-list-filters pds-os-list-filters')->open() !!}
    {!! html()->hidden('status', $approvalTab ?? request('status', 'pending')) !!}
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
        {!! html()->label(__('message.last_active'), 'last_actived_at')->class('pds-list-filters__label') !!}
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
        @isset($reset_file_button)
            {!! $reset_file_button !!}
        @endisset
    </div>
{!! html()->form()->close() !!}
<script>
    $(document).ready(function () {
        $(".select2js").select2({ width: "100%" });
    });
</script>
