<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter pds-subadmin-list-page">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch card-height pds-page-card">
                    <div class="card-header d-flex justify-content-between align-items-center pds-page-header">
                        <div class="header-title">
                            <h4 class="card-title mb-0 pds-page-title">{{ $pageTitle ?? ''}}</h4>
                        </div>

                        <div class="card-header-toolbar pds-page-actions">
                            @if(isset($button))
                            {!! $button !!}
                            @endif
                        </div>
                    </div>
                    <div class="card-body pds-page-body">
                        <div class="pds-subadmin-toolbar">
                            <div class="pds-subadmin-toolbar__start">
                                @if(!empty($multi_checkbox_delete))
                                    {!! $multi_checkbox_delete !!}
                                @endif
                            </div>
                            <div class="pds-subadmin-toolbar__end">
                                <label class="pds-subadmin-toolbar__search" for="pds-subadmin-search">
                                    <input type="search"
                                           id="pds-subadmin-search"
                                           placeholder="{{ __('message.search') }}"
                                           autocomplete="off">
                                </label>
                            </div>
                        </div>
                        <div class="pds-table-shell">
                            {{ $dataTable->table(['class' => 'table w-100 pds-datatable'],false) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @section('bottom_script')
       {{ $dataTable->scripts() }}
       <script>
            $(function () {
                var csrfToken = $('meta[name="csrf-token"]').attr('content');
                var workStatusBase = @json(url('sub-admin'));
                var $table = $('.pds-subadmin-list-page table.pds-datatable').first();
                var $search = $('#pds-subadmin-search');

                function hideDtChrome() {
                    $('.pds-subadmin-list-page .dataTables_filter').hide();
                    $('.pds-subadmin-list-page .dataTables_wrapper > .row.align-items-center').hide();
                    $('#deleteSelectedBtn')
                        .addClass('is-visible pds-subadmin-delete-btn')
                        .removeAttr('hidden')
                        .show();
                    if (typeof window.toggleDeleteButton === 'function') {
                        window.toggleDeleteButton();
                    }
                }

                hideDtChrome();
                $table.on('init.dt draw.dt', hideDtChrome);
                $(document).on('admin-spa:navigated', function () {
                    setTimeout(hideDtChrome, 50);
                });
                window.setTimeout(hideDtChrome, 50);
                window.setTimeout(hideDtChrome, 300);

                $search.on('keyup input', function () {
                    if ($.fn.DataTable && $.fn.DataTable.isDataTable($table)) {
                        $table.DataTable().search($search.val()).draw();
                    }
                });

                $(document).on('change', '.js-employee-work-toggle', function () {
                    var $input = $(this);
                    if ($input.data('saving')) {
                        return;
                    }
                    var id = $input.data('id');
                    var workOn = $input.prop('checked') ? 1 : 0;
                    var previous = !workOn;
                    var $switch = $input.closest('.pds-dm-work-switch');
                    $input.data('saving', true).prop('disabled', true);

                    $.ajax({
                        url: workStatusBase + '/' + id + '/work-status',
                        method: 'POST',
                        data: {
                            work_on: workOn,
                            _token: csrfToken
                        },
                        success: function (res) {
                            var on = !!res.work_on;
                            $input.prop('checked', on);
                            $switch.toggleClass('is-on', on).toggleClass('is-off', !on);
                            $switch.find('.pds-dm-work-switch__label').text(res.label || (on ? 'On' : 'Off'));
                            $switch.attr('title', on
                                ? @json(__('message.employee_work_on_hint'))
                                : @json(__('message.employee_work_off_hint')));
                            if (typeof Snackbar !== 'undefined' && res.message) {
                                Snackbar.show({ text: res.message, pos: 'bottom-center' });
                            }
                        },
                        error: function (xhr) {
                            $input.prop('checked', previous);
                            var msg = (xhr.responseJSON && xhr.responseJSON.message) || @json(__('message.something_went_wrong'));
                            if (typeof Snackbar !== 'undefined') {
                                Snackbar.show({ text: msg, pos: 'bottom-center', backgroundColor: '#dc3545' });
                            } else {
                                alert(msg);
                            }
                        },
                        complete: function () {
                            $input.data('saving', false).prop('disabled', false);
                        }
                    });
                });
            });
       </script>
    @endsection
</x-master-layout>
