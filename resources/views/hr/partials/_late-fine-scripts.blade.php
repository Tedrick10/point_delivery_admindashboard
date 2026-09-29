{{-- Late Fine sheet scripts; expects jQuery (+ optional select2/iziToast) --}}
@if($canEdit)
            <script>
                (function () {
                    var csrf = $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}';
                    var rowUrlBase = '/hr/late-fine/row';
                    var timers = {};

                    function money(n) {
                        return new Intl.NumberFormat().format(Math.round(Number(n) || 0));
                    }

                    function toastError(msg) {
                        if (window.iziToast) {
                            iziToast.error({title: 'Error', message: msg || 'Save failed', position: 'topRight'});
                        }
                    }

                    function fieldValue($input) {
                        var val = $input.val();
                        if (val !== '') {
                            return val;
                        }
                        return $input.is('[type="number"]') || $input.attr('data-pds-number') === '1' ? '0' : '';
                    }

                    function saveRow($tr, immediate) {
                        var id = $tr.data('row-id');
                        if (!id) return;

                        clearTimeout(timers[id]);
                        var run = function () {
                            var payload = {_token: csrf, _method: 'PUT'};
                            $tr.find('.late-input').each(function () {
                                payload[$(this).data('field')] = fieldValue($(this));
                            });
                            $.ajax({
                                url: rowUrlBase + '/' + id,
                                method: 'POST',
                                data: payload,
                                headers: {'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest'},
                                success: function (res) {
                                    if (!res.success) {
                                        toastError(res.message);
                                        return;
                                    }
                                    var d = res.data;
                                    $tr.find('.js-fine-minutes').text(d.fine_minutes);
                                    $tr.find('.js-late-fine-amount').text(money(d.late_fine_amount));
                                    if (d.absent_dates !== undefined) {
                                        $tr.find('[data-field="absent_dates"]').val(d.absent_dates);
                                    }
                                    if (d.absent_days !== undefined) {
                                        $tr.find('[data-field="absent_days"]').val(d.absent_days);
                                    }
                                    $tr.find('.js-absent-fine').text(money(d.absent_fine_amount));
                                    $tr.find('.js-total-fine').text(money(d.total_fine));
                                    $tr.find('.js-grand-total').text(money(d.grand_total));
                                },
                                error: function (xhr) {
                                    var msg = (xhr.responseJSON && xhr.responseJSON.message)
                                        ? xhr.responseJSON.message
                                        : 'Save failed (' + xhr.status + ')';
                                    toastError(msg);
                                }
                            });
                        };

                        if (immediate) {
                            run();
                        } else {
                            timers[id] = setTimeout(run, 400);
                        }
                    }

                    if ($.fn.select2) {
                        var staffSelectOpts = {
                            width: '100%',
                            placeholder: '{{ __('message.name') }} ရွေးပါ',
                            allowClear: true,
                            matcher: function (params, data) {
                                if ($.trim(params.term || '') === '') {
                                    return data;
                                }
                                if (typeof data.text === 'undefined') {
                                    return null;
                                }
                                var term = params.term.toLowerCase();
                                var text = (data.text || '').toLowerCase();
                                return text.indexOf(term) > -1 ? data : null;
                            }
                        };
                        $('#extra-fine-staff').select2($.extend({}, staffSelectOpts, {
                            dropdownParent: $('#extra-fine-staff').closest('.pds-hr-extra__form')
                        }));
                        $('#bag-deduction-staff').select2($.extend({}, staffSelectOpts, {
                            dropdownParent: $('#bag-deduction-form')
                        }));
                    }

                    $('#late-fine-table').on('input', '.late-input:not([data-field="absent_dates"])', function () {
                        saveRow($(this).closest('tr'), false);
                    }).on('change blur', '.late-input', function () {
                        saveRow($(this).closest('tr'), true);
                    }).on('keydown', '[data-field="absent_dates"]', function (e) {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            saveRow($(this).closest('tr'), true);
                            $(this).blur();
                        }
                    });
                })();
            </script>

@endif
        <script>
            (function ($) {
                function money(n) {
                    return new Intl.NumberFormat().format(Math.round(Number(n) || 0));
                }

                function applyExtraListFilter(listKey) {
                    var $list = $('[data-extra-list="' + listKey + '"]');
                    if (!$list.length) return;

                    var search = String($('.js-extra-list-search[data-list="' + listKey + '"]').val() || '')
                        .trim()
                        .toLowerCase();
                    var nameFilter = String($('.js-extra-list-name-filter[data-list="' + listKey + '"]').val() || '')
                        .trim()
                        .toLowerCase();
                    var groupFilter = String($('.js-extra-list-group-filter[data-list="' + listKey + '"]').val() || '')
                        .trim()
                        .toLowerCase();

                    var visible = 0;
                    var total = 0;
                    $list.find('.pds-hr-extra__row').each(function () {
                        var $row = $(this);
                        var name = String($row.attr('data-name') || '').toLowerCase();
                        var group = String($row.attr('data-group') || '').toLowerCase();
                        var amount = Number($row.attr('data-amount') || 0);
                        var match = true;

                        if (search && name.indexOf(search) === -1) {
                            match = false;
                        }
                        if (match && nameFilter && name !== nameFilter) {
                            match = false;
                        }
                        if (match && groupFilter && group !== groupFilter) {
                            match = false;
                        }

                        $row.prop('hidden', !match);
                        if (match) {
                            visible += 1;
                            total += amount;
                            $row.find('.js-extra-row-no').text(visible);
                        }
                    });

                    var hasRows = $list.find('.pds-hr-extra__row').length > 0;
                    var filtering = !!(search || nameFilter || groupFilter);
                    $list.find('.pds-hr-extra__empty--filter').prop('hidden', !(hasRows && filtering && visible === 0));
                    $list.find('.pds-hr-extra__footer').prop('hidden', !(hasRows && (!filtering || visible > 0)));
                    $list.find('.js-extra-list-total[data-list="' + listKey + '"]').text(money(total));
                    $('.js-extra-list-pill-total[data-list="' + listKey + '"]').text(money(total));
                }

                $(document).on('input', '.js-extra-list-search', function () {
                    applyExtraListFilter($(this).data('list'));
                });
                $(document).on('change', '.js-extra-list-name-filter, .js-extra-list-group-filter', function () {
                    applyExtraListFilter($(this).data('list'));
                });
                $(document).on('click', '.js-extra-list-clear', function () {
                    var listKey = $(this).data('list');
                    $('.js-extra-list-search[data-list="' + listKey + '"]').val('');
                    $('.js-extra-list-name-filter[data-list="' + listKey + '"]').val('');
                    $('.js-extra-list-group-filter[data-list="' + listKey + '"]').val('');
                    applyExtraListFilter(listKey);
                });
            })(jQuery);
        </script>
