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

            var $form = $('#pds-os-list-filter-form');
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

            var searchTimer = null;
            var lastTerm = null;

            var hideDtChrome = function () {
                $('.pds-os-list-page .pds-os-list-table-shell .dataTables_wrapper > .row.align-items-center').hide();
                $('.pds-os-list-page .dataTables_filter').hide();
            };

            var hideProcessing = function () {
                var $wrap = $table.closest('.dataTables_wrapper');
                $wrap.find('.dataTables_processing').hide().css('display', 'none');
                $('.pds-os-list-page .dataTables_processing').hide().css('display', 'none');
                if ($.fn.DataTable.isDataTable($table)) {
                    try {
                        var api = $table.DataTable();
                        if (typeof api.processing === 'function') {
                            api.processing(false);
                        }
                        var settings = api.settings()[0];
                        if (settings) {
                            settings.bDrawing = false;
                        }
                    } catch (e) {}
                }
            };

            var abortPending = function (api) {
                try {
                    var settings = api.settings()[0];
                    if (settings && settings.jqXHR && settings.jqXHR.readyState !== 4) {
                        settings.jqXHR.abort();
                    }
                } catch (e) {}
            };

            var syncUrlQuery = function (term) {
                try {
                    var url = new URL(window.location.href);
                    if (term) {
                        url.searchParams.set('q', term);
                    } else {
                        url.searchParams.delete('q');
                    }
                    window.history.replaceState({}, '', url.pathname + url.search + url.hash);
                } catch (e) {}
            };

            var runSearch = function (immediate) {
                hideDtChrome();
                if (!$.fn.DataTable.isDataTable($table)) {
                    return;
                }

                var term = String($input.val() || '').trim();
                if (term === lastTerm && !immediate) {
                    return;
                }

                var api = $table.DataTable();
                abortPending(api);
                lastTerm = term;
                syncUrlQuery(term);

                // Clear must refresh immediately; typing is debounced.
                api.search(term);
                api.draw();

                if (!term) {
                    // Aborted/empty redraws can leave the Processing label stuck.
                    window.setTimeout(hideProcessing, 0);
                    window.setTimeout(hideProcessing, 120);
                }
            };

            var scheduleSearch = function () {
                var term = String($input.val() || '').trim();
                window.clearTimeout(searchTimer);
                if (term === '') {
                    runSearch(true);
                    return;
                }
                searchTimer = window.setTimeout(function () {
                    runSearch(false);
                }, 220);
            };

            $input.off('.pdsOsSearch');
            $form.off('.pdsOsSearch');
            $('#pds-os-list-search-btn').off('.pdsOsSearch');
            $table.off('init.dt.pdsOsSearch draw.dt.pdsOsSearch processing.dt.pdsOsSearch xhr.dt.pdsOsSearch');

            $form.on('submit.pdsOsSearch', function (e) {
                e.preventDefault();
                window.clearTimeout(searchTimer);
                runSearch(true);
            });

            $input.on('input.pdsOsSearch search.pdsOsSearch', scheduleSearch);
            $input.on('keydown.pdsOsSearch', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    window.clearTimeout(searchTimer);
                    runSearch(true);
                } else if (e.key === 'Escape') {
                    e.preventDefault();
                    $input.val('');
                    window.clearTimeout(searchTimer);
                    runSearch(true);
                }
            });
            $('#pds-os-list-search-btn').on('click.pdsOsSearch', function (e) {
                e.preventDefault();
                window.clearTimeout(searchTimer);
                runSearch(true);
            });

            $table.on('init.dt.pdsOsSearch draw.dt.pdsOsSearch', function () {
                hideDtChrome();
                hideProcessing();
            });
            $table.on('processing.dt.pdsOsSearch', function (e, settings, processing) {
                if (!processing) {
                    hideProcessing();
                }
            });
            $table.on('xhr.dt.pdsOsSearch', function () {
                hideProcessing();
            });

            hideDtChrome();
            window.setTimeout(hideDtChrome, 50);

            var initial = String($input.val() || '').trim();
            if (initial !== '') {
                window.setTimeout(function () {
                    runSearch(true);
                }, 80);
            } else {
                lastTerm = '';
            }
        }

        if (window.jQuery) {
            jQuery(bootOsListSearch);
        } else {
            document.addEventListener('DOMContentLoaded', bootOsListSearch);
        }
        document.addEventListener('pds:page-ready', bootOsListSearch);
    })();
</script>
