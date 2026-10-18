{!! html()->form('GET', url()->current())->class('pds-os-search')->id('pds-os-list-filter-form')->open() !!}
    {!! html()->hidden('status', $approvalTab ?? request('status', 'pending')) !!}
    @if(($selectedBranchId ?? request('branch_id')) )
        {!! html()->hidden('branch_id', (int) ($selectedBranchId ?? request('branch_id'))) !!}
    @endif

    <label for="pds-os-list-search" class="pds-os-search__label">{{ __('message.search') }}</label>
    <div class="pds-os-search__row">
        <div class="pds-os-search__field">
            <i class="fas fa-search pds-os-search__icon" aria-hidden="true"></i>
            <input type="search"
                   id="pds-os-list-search"
                   name="q"
                   class="pds-os-search__input"
                   placeholder="{{ __('message.os_name') }} / {{ __('message.phone') }}"
                   autocomplete="off"
                   value="{{ request('q', '') }}">
        </div>
        <button type="submit" id="pds-os-list-search-btn" class="pds-os-search__btn">
            <i class="fas fa-search" aria-hidden="true"></i>
            <span>{{ __('message.search') }}</span>
        </button>
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
