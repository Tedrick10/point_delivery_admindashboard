<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter pds-deliveryman-list-page">
        <style>
            .pds-dm-branch-tabs { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
            .pds-dm-branch-tab {
                display: inline-flex; align-items: center; gap: 8px;
                border: 1px solid #e2e8f0; background: #fff; color: #334155;
                border-radius: 999px; padding: 8px 14px; font-weight: 700; text-decoration: none;
            }
            .pds-dm-branch-tab .badge { background: #f1f5f9; color: #64748b; }
            .pds-dm-branch-tab:hover { border-color: #fdba74; color: #c2410c; text-decoration: none; }
            .pds-dm-branch-tab.is-active {
                background: linear-gradient(135deg, #FE6F07, #ff8f3d);
                border-color: transparent; color: #fff;
            }
            .pds-dm-branch-tab.is-active .badge { background: rgba(255,255,255,.22); color: #fff; }
        </style>
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch card-height pds-page-card">
                    <div class="card-header d-flex justify-content-between align-items-center pds-page-header">
                        <div class="header-title">
                            <h4 class="card-title mb-0 pds-page-title">{{ $pageTitle ?? '' }}</h4>
                        </div>
                        <div class="card-header-toolbar pds-page-actions d-flex flex-wrap align-items-center gap-2">
                            @if(!empty($export))
                                {!! $export !!}
                            @endif
                            @if(!empty($button))
                                {!! $button !!}
                            @endif
                        </div>
                    </div>

                    <div class="card-body pds-page-body pds-deliveryman-list-body">
                        @if(isset($multi_checkbox_delete))
                            <div class="pds-bulk-actions mb-2">
                                {!! $multi_checkbox_delete !!}
                            </div>
                        @endif

                        @include('partials._branch-tabs', [
                            'branchTabs' => $branchTabs ?? collect(),
                            'selectedBranchId' => ($selectedBranchId ?? 0) > 0 ? $selectedBranchId : null,
                            'branchTabCounts' => $branchTabCounts ?? [],
                            'allCount' => $allRiderCount ?? null,
                            'includeAll' => false,
                            'routeName' => 'deliveryman.index',
                            'routeQuery' => array_filter(['status' => request('status')]),
                        ])

                        @include('global.deliveryman-datatable')

                        <div class="pds-table-shell pds-deliveryman-table-shell">
                            {{ $dataTable->table(['class' => 'table w-100 pds-datatable pds-deliveryman-datatable'], false) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @section('bottom_script')
        {{ $dataTable->scripts() }}
        <style>
            body.pds-admin .pds-deliveryman-list-page .pds-inline-contact {
                cursor: pointer;
                border-bottom: 1px dashed transparent;
                display: inline-block;
                min-width: 7rem;
                white-space: nowrap;
            }
            body.pds-admin .pds-deliveryman-list-page .pds-inline-contact:hover {
                border-bottom-color: #94a3b8;
            }
            body.pds-admin .pds-deliveryman-list-page .pds-inline-contact-input {
                width: 100%;
                min-width: 9rem;
                max-width: 12rem;
                padding: 0.2rem 0.4rem;
                border: 1px solid #ced4da;
                border-radius: 0.25rem;
                font-size: inherit;
                line-height: 1.4;
            }
            body.pds-admin .pds-deliveryman-list-page .pds-inline-contact-input.is-invalid {
                border-color: #dc3545;
            }
        </style>
        <script>
            $(document).ready(function() {
                $('.pds-list-filters .select2js').select2({ width: '100%' });

                var $table = $('table.pds-datatable');
                var contactUpdateUrl = @json(url('deliveryman'));
                var workStatusUrl = @json(url('deliveryman'));
                var csrfToken = $('meta[name="csrf-token"]').attr('content');

                function contactCells() {
                    return $table.find('.pds-inline-contact');
                }

                function renderContactSpan($cell, value, display) {
                    $cell
                        .attr('data-value', value || '')
                        .text(display || value || '-')
                        .removeClass('is-editing');
                }

                function openNextContact($currentSpan) {
                    var $cells = contactCells();
                    var idx = $cells.index($currentSpan);
                    if (idx < 0) {
                        return;
                    }
                    var $next = $cells.eq(idx + 1);
                    if ($next.length) {
                        setTimeout(function() {
                            $next.trigger('click');
                        }, 30);
                    }
                }

                function saveContact($input, advance) {
                    if ($input.data('saving')) {
                        return;
                    }

                    var $span = $input.data('span');
                    if (!$span || !$span.length) {
                        return;
                    }

                    var userId = $span.data('user-id');
                    var original = String($span.attr('data-value') || '');
                    var nextValue = String($input.val() || '').replace(/\s+/g, '').trim();

                    if (!nextValue) {
                        $input.addClass('is-invalid');
                        return;
                    }

                    if (nextValue === original) {
                        renderContactSpan($span, original, original || '-');
                        $input.replaceWith($span);
                        if (advance) {
                            openNextContact($span);
                        }
                        return;
                    }

                    $input.data('saving', true).prop('disabled', true);

                    $.ajax({
                        url: contactUpdateUrl + '/' + userId + '/contact-number',
                        method: 'POST',
                        data: {
                            contact_number: nextValue,
                            _token: csrfToken
                        },
                        success: function(res) {
                            var saved = res.contact_number || nextValue;
                            var display = res.display || saved;
                            renderContactSpan($span, saved, display);
                            $input.replaceWith($span);
                            if (typeof Snackbar !== 'undefined' && res.message) {
                                Snackbar.show({ text: res.message, pos: 'bottom-center' });
                            }
                            if (advance) {
                                openNextContact($span);
                            }
                        },
                        error: function(xhr) {
                            $input.data('saving', false).prop('disabled', false).addClass('is-invalid').focus();
                            var msg = (xhr.responseJSON && (xhr.responseJSON.message || (xhr.responseJSON.errors && xhr.responseJSON.errors.contact_number && xhr.responseJSON.errors.contact_number[0])))
                                || 'Unable to update contact number';
                            if (typeof Snackbar !== 'undefined') {
                                Snackbar.show({ text: msg, pos: 'bottom-center', backgroundColor: '#dc3545' });
                            } else {
                                alert(msg);
                            }
                        }
                    });
                }

                $table.on('click', '.pds-inline-contact', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    var $span = $(this);
                    if ($span.hasClass('is-editing')) {
                        return;
                    }

                    $table.find('.pds-inline-contact-input').each(function() {
                        var $open = $(this);
                        if ($open.data('saving')) {
                            return;
                        }
                        var $openSpan = $open.data('span');
                        if ($openSpan && $openSpan.length) {
                            renderContactSpan($openSpan, $openSpan.attr('data-value'), $openSpan.text());
                            $open.replaceWith($openSpan);
                        }
                    });

                    var current = String($span.attr('data-value') || '');
                    var displayText = $span.text();
                    $span.data('display-text', displayText);

                    var $input = $('<input type="text" class="pds-inline-contact-input" maxlength="20" />')
                        .val(current)
                        .data('span', $span);

                    $span.addClass('is-editing').replaceWith($input);
                    $input.focus().select();
                });

                $table.on('keydown', '.pds-inline-contact-input', function(e) {
                    if (e.key === 'Enter' || e.keyCode === 13) {
                        e.preventDefault();
                        saveContact($(this), true);
                    } else if (e.key === 'Escape' || e.keyCode === 27) {
                        e.preventDefault();
                        var $input = $(this);
                        if ($input.data('saving')) {
                            return;
                        }
                        var $span = $input.data('span');
                        if ($span && $span.length) {
                            var display = $span.data('display-text') || $span.attr('data-value') || '-';
                            renderContactSpan($span, $span.attr('data-value'), display);
                            $input.replaceWith($span);
                        }
                    }
                });

                $table.on('blur', '.pds-inline-contact-input', function() {
                    var $input = $(this);
                    setTimeout(function() {
                        if (!$input.closest('body').length || $input.data('saving') || $input.prop('disabled')) {
                            return;
                        }
                        var $span = $input.data('span');
                        if ($span && $span.length) {
                            var display = $span.data('display-text') || $span.attr('data-value') || '-';
                            renderContactSpan($span, $span.attr('data-value'), display);
                            $input.replaceWith($span);
                        }
                    }, 120);
                });

                $table.on('change', '.js-dm-work-toggle', function() {
                    const $input = $(this);
                    if ($input.data('saving')) {
                        return;
                    }
                    const id = $input.data('id');
                    const workOn = $input.prop('checked') ? 1 : 0;
                    const previous = !workOn;
                    const $switch = $input.closest('.pds-dm-work-switch');
                    $input.data('saving', true).prop('disabled', true);

                    $.ajax({
                        url: workStatusUrl + '/' + id + '/work-status',
                        method: 'POST',
                        data: {
                            work_on: workOn,
                            _token: csrfToken,
                        },
                        success: function(res) {
                            const on = !!res.work_on;
                            $input.prop('checked', on);
                            $switch.toggleClass('is-on', on).toggleClass('is-off', !on);
                            $switch.find('.pds-dm-work-switch__label').text(res.label || (on ? 'On' : 'Off'));
                            $switch.attr('title', on ? @json(__('message.rider_work_on_hint')) : @json(__('message.rider_work_off_hint')));
                            if (typeof Snackbar !== 'undefined' && res.message) {
                                Snackbar.show({ text: res.message, pos: 'bottom-center' });
                            }
                        },
                        error: function(xhr) {
                            $input.prop('checked', previous);
                            const msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Unable to update work status';
                            if (typeof Snackbar !== 'undefined') {
                                Snackbar.show({ text: msg, pos: 'bottom-center', backgroundColor: '#dc3545' });
                            } else {
                                alert(msg);
                            }
                        },
                        complete: function() {
                            $input.data('saving', false).prop('disabled', false);
                        },
                    });
                });
            });
        </script>
    @endsection
</x-master-layout>
