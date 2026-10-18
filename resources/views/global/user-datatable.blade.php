{!! html()->form('GET', url()->current())->class('pds-list-filters pds-os-list-filters pds-os-list-filters--search')->id('pds-os-list-filter-form')->open() !!}
    {!! html()->hidden('status', $approvalTab ?? request('status', 'pending')) !!}
    @if(($selectedBranchId ?? request('branch_id')) )
        {!! html()->hidden('branch_id', (int) ($selectedBranchId ?? request('branch_id'))) !!}
    @endif

    <div class="pds-list-filters__field pds-list-filters__field--grow">
        <label for="pds-os-list-search" class="pds-list-filters__label">{{ __('message.search') }}</label>
        <input type="search"
               id="pds-os-list-search"
               name="q"
               class="pds-dispatch-input"
               placeholder="{{ __('message.os_name') }} / {{ __('message.phone') }}"
               autocomplete="off"
               value="{{ request('q', '') }}">
    </div>

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
        <button type="button" id="pds-os-list-search-btn" class="btn btn-sm btn-primary pds-os-filter-apply">
            <i class="fas fa-search" aria-hidden="true"></i>
            {{ __('message.search') }}
        </button>
        <button type="submit" class="btn btn-sm btn-primary pds-os-filter-apply">{{ __('message.apply_filter') }}</button>
        @isset($reset_file_button)
            <span class="pds-os-filter-reset">{!! $reset_file_button !!}</span>
        @endisset
    </div>
{!! html()->form()->close() !!}
<script>
    (function () {
        function bootOsListSearch() {
            if (!window.jQuery || !jQuery.fn.DataTable) {
                return;
            }
            var $ = jQuery;
            $(".pds-os-list-page .select2js").select2({ width: "100%" });

            var $input = $('#pds-os-list-search');
            var $table = $('.pds-os-list-page table.pds-os-list-table, .pds-os-list-page table.pds-datatable').first();
            if (!$input.length || !$table.length) {
                return;
            }

            try {
                var urlQ = new URLSearchParams(window.location.search).get('q');
                if (urlQ && !String($input.val() || '').trim()) {
                    $input.val(urlQ);
                }
            } catch (e) {}

            var hideDtChrome = function () {
                $('.pds-os-list-page .pds-os-list-table-shell .dataTables_wrapper > .row.align-items-center').hide();
                $('.pds-os-list-page .dataTables_filter').hide();
            };

            var runSearch = function () {
                hideDtChrome();
                if (!$.fn.DataTable.isDataTable($table)) {
                    return;
                }
                $table.DataTable().search(String($input.val() || '').trim()).draw();
            };

            $input.off('.pdsOsSearch');
            $('#pds-os-list-search-btn').off('.pdsOsSearch');
            $input.on('keyup.pdsOsSearch input.pdsOsSearch', function () {
                runSearch();
            });
            $input.on('keydown.pdsOsSearch', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    runSearch();
                }
            });
            $('#pds-os-list-search-btn').on('click.pdsOsSearch', function (e) {
                e.preventDefault();
                runSearch();
            });

            $table.off('init.dt.pdsOsSearch draw.dt.pdsOsSearch');
            $table.on('init.dt.pdsOsSearch draw.dt.pdsOsSearch', hideDtChrome);
            hideDtChrome();
            window.setTimeout(hideDtChrome, 50);
            window.setTimeout(function () {
                if (String($input.val() || '').trim() !== '') {
                    runSearch();
                }
            }, 120);
        }

        if (window.jQuery) {
            jQuery(bootOsListSearch);
        } else {
            document.addEventListener('DOMContentLoaded', bootOsListSearch);
        }
        document.addEventListener('pds:page-ready', bootOsListSearch);
    })();
</script>
