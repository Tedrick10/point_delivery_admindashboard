<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter pds-hr-page">
        <div class="pds-hr-hero">
            <div class="pds-hr-hero__copy">
                <div class="pds-hr-hero__eyebrow">
                    <i class="fas fa-user-clock" aria-hidden="true"></i>
                    <span>{{ __('message.hr_payroll') }}</span>
                </div>
                <h4 class="pds-hr-hero__title">{{ $pageTitle }}</h4>
                <p class="pds-hr-hero__subtitle">{{ __('message.hr_accounts_sync_hint') }}</p>
            </div>
            <div class="pds-hr-hero__stats">
                <div class="pds-hr-stat">
                    <span class="pds-hr-stat__value">{{ number_format($sum_grand) }}</span>
                    <span class="pds-hr-stat__label">{{ __('message.hr_total_fine') }}</span>
                </div>
                <div class="pds-hr-stat pds-hr-stat--soft">
                    <span class="pds-hr-stat__value">{{ $rows->count() }}</span>
                    <span class="pds-hr-stat__label">{{ __('message.hr_people') }}</span>
                </div>
            </div>
        </div>

        <div class="pds-hr-toolbar">
            <div class="pds-hr-month pds-hr-month--end">
                <a href="{{ route('hr.late-fine.index', ['month' => $prevMonth]) }}" class="pds-hr-month__btn"><i class="fas fa-chevron-left"></i></a>
                <span class="pds-hr-month__label">{{ $monthLabel }}</span>
                <a href="{{ route('hr.late-fine.index', ['month' => $nextMonth]) }}" class="pds-hr-month__btn"><i class="fas fa-chevron-right"></i></a>
            </div>
        </div>

        <div class="pds-hr-panel mb-3">
            <div class="table-responsive">
                <table class="table pds-hr-table mb-0" id="late-fine-table">
                    <thead>
                    <tr>
                        <th class="pds-hr-col-no">#</th>
                        <th>{{ __('message.name') }}</th>
                        <th>{{ __('message.hr_late_minutes') }}</th>
                        <th>{{ __('message.hr_allowance_minutes') }}</th>
                        <th>{{ __('message.hr_fine_minutes') }}</th>
                        <th>1မိနစ်/{{ (int) ($finePerMinuteDefault ?? ($rows->first()->fine_per_minute ?? 100)) }}</th>
                        <th>{{ __('message.hr_fine_amount') }}</th>
                        <th class="pds-hr-th-fp">
                            <span class="pds-hr-th-stack">
                                <span class="pds-hr-th-kicker"><i class="fas fa-fingerprint" aria-hidden="true"></i> {{ __('message.hr_absent_dates_kicker') }}</span>
                                <span class="pds-hr-th-main">{{ __('message.hr_absent_dates') }}</span>
                            </span>
                        </th>
                        <th class="pds-hr-th-fp">
                            <span class="pds-hr-th-stack">
                                <span class="pds-hr-th-kicker"><i class="fas fa-fingerprint" aria-hidden="true"></i> {{ __('message.hr_absent_days_kicker') }}</span>
                                <span class="pds-hr-th-main">{{ __('message.hr_absent_days') }}</span>
                            </span>
                        </th>
                        <th class="pds-hr-th-fp pds-hr-th-fp--fine">
                            <span class="pds-hr-th-stack">
                                <span class="pds-hr-th-main">{{ __('message.hr_absent_fine') }}</span>
                            </span>
                        </th>
                        <th>{{ __('message.hr_total_fine') }}</th>
                        <th>{{ __('message.total') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($rows as $i => $row)
                        @php
                            $role = $row->staff?->user?->user_type
                                ? ucwords(str_replace('_', ' ', $row->staff->user->user_type))
                                : ($row->staff?->staff_group === 'rider' ? 'Delivery Man' : 'Office');
                            $initial = strtoupper(substr(preg_replace('/\s+/', '', (string) ($row->staff?->name ?? '?')), 0, 1));
                            $isRider = ($row->staff?->staff_group ?? '') === 'rider';
                        @endphp
                        <tr data-row-id="{{ $row->id }}">
                            <td class="pds-hr-col-no">{{ $i + 1 }}</td>
                            <td>
                                <div class="pds-hr-person">
                                    <span class="pds-hr-avatar{{ $isRider ? ' is-rider' : '' }}">{{ $initial }}</span>
                                    <div>
                                        <div class="pds-hr-person__name">{{ $row->staff?->name }}</div>
                                        <span class="pds-hr-badge{{ $isRider ? ' is-rider' : '' }}">{{ $role }}</span>
                                    </div>
                                </div>
                            </td>
                            <td><input type="number" min="0" class="pds-hr-input late-input" data-field="late_minutes" value="{{ $row->late_minutes }}" @disabled(! $canEdit)></td>
                            <td class="pds-hr-num pds-hr-readonly" title="{{ __('message.hr_readonly_super_admin') }}">{{ (int) $row->allowance_minutes }}</td>
                            <td class="js-fine-minutes pds-hr-num">{{ $row->fine_minutes }}</td>
                            <td class="pds-hr-num pds-hr-readonly" title="{{ __('message.hr_readonly_super_admin') }}">{{ (int) $row->fine_per_minute }}</td>
                            <td class="js-late-fine-amount pds-hr-num">{{ number_format($row->late_fine_amount) }}</td>
                            <td><input type="text" class="pds-hr-input late-input" data-field="absent_dates" value="{{ $row->absent_dates }}" placeholder="8/9/10" @disabled(! $canEdit)></td>
                            <td><input type="number" min="0" class="pds-hr-input late-input" data-field="absent_days" value="{{ $row->absent_days }}" @disabled(! $canEdit)></td>
                            <td class="js-absent-fine pds-hr-num">{{ number_format($row->absent_fine_amount) }}</td>
                            <td class="js-total-fine pds-hr-num pds-hr-num--strong">{{ number_format($row->total_fine) }}</td>
                            <td class="js-grand-total pds-hr-num pds-hr-num--accent">{{ number_format($row->sheet_total) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="pds-hr-empty">{{ __('message.hr_no_accounts_hint') }}</td>
                        </tr>
                    @endforelse
                    </tbody>
                    @if($rows->isNotEmpty())
                        <tfoot>
                        <tr>
                            <td colspan="10" class="text-right">{{ __('message.total') }}</td>
                            <td class="pds-hr-num pds-hr-num--strong">{{ number_format($sum_total_fine) }}</td>
                            <td class="pds-hr-num pds-hr-num--accent">{{ number_format($sum_grand) }}</td>
                        </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        <div class="pds-hr-panel pds-hr-extra mb-3">
            <div class="pds-hr-extra__head">
                <div class="pds-hr-extra__title-wrap">
                    <span class="pds-hr-extra__icon"><i class="fas fa-receipt" aria-hidden="true"></i></span>
                    <div>
                        <h5>{{ __('message.hr_late_fine_item') }}</h5>
                        <p>{{ __('message.hr_late_fine_item_hint') }}</p>
                    </div>
                </div>
                <div class="pds-hr-extra__total-pill">
                    <span class="pds-hr-extra__total-pill-label">{{ __('message.total') }}</span>
                    <span class="pds-hr-extra__total-pill-value">{{ number_format($sum_incidents) }}</span>
                </div>
            </div>

            @if($canEdit)
                <form method="POST" action="{{ route('hr.late-fine.item.store') }}" class="pds-hr-extra__form">
                    @csrf
                    <input type="hidden" name="month" value="{{ $monthValue }}">
                    <div class="pds-hr-extra__field">
                        <label><i class="fas fa-user"></i> {{ __('message.name') }}</label>
                        <select name="staff_id" id="extra-fine-staff" class="pds-hr-extra__control pds-hr-extra__select2" required>
                            <option value="">{{ __('message.name') }} ရွေးပါ</option>
                            @foreach($staffOptions as $opt)
                                <option value="{{ $opt->id }}"
                                        data-group="{{ $opt->staff_group === 'rider' ? 'rider' : 'office' }}">
                                    {{ $opt->name }}
                                    · {{ $opt->staff_group === 'rider' ? __('message.hr_group_rider') : __('message.hr_group_office') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="pds-hr-extra__field pds-hr-extra__field--grow">
                        <label><i class="fas fa-align-left"></i> {{ __('message.hr_extra_fine_about') }}</label>
                        <input type="text" name="description" class="pds-hr-extra__control" required
                               placeholder="ဥပမာ — 30.10.2022 / Way Change နောက်ကျ">
                    </div>
                    <div class="pds-hr-extra__field pds-hr-extra__field--amount">
                        <label><i class="fas fa-coins"></i> {{ __('message.hr_extra_fine_amount') }}</label>
                        <input type="number" min="0" step="1" name="amount" class="pds-hr-extra__control" required placeholder="0">
                    </div>
                    <div class="pds-hr-extra__field pds-hr-extra__field--action">
                        <button class="pds-hr-extra__add-btn" type="submit">
                            <i class="fas fa-plus"></i>
                            <span>{{ __('message.add') }}</span>
                        </button>
                    </div>
                </form>
            @endif

            <div class="pds-hr-extra__list{{ $canEdit ? '' : ' is-readonly' }}">
                <div class="pds-hr-extra__list-head">
                    <span class="pds-hr-extra__col-no">#</span>
                    <span class="pds-hr-extra__col-name">{{ __('message.name') }}</span>
                    <span class="pds-hr-extra__col-about">{{ __('message.hr_extra_fine_about') }}</span>
                    <span class="pds-hr-extra__col-fine">{{ __('message.hr_extra_fine_amount') }}</span>
                    @if($canEdit)
                        <span class="pds-hr-extra__col-action"></span>
                    @endif
                </div>

                @forelse($items as $i => $item)
                    @php
                        $itemName = $item->staff?->name ?? ($item->staff_code ?: '—');
                        $itemInitial = strtoupper(substr(preg_replace('/\s+/', '', (string) $itemName), 0, 1) ?: '?');
                    @endphp
                    <div class="pds-hr-extra__row">
                        <span class="pds-hr-extra__col-no">{{ $i + 1 }}</span>
                        <span class="pds-hr-extra__col-name">
                            <span class="pds-hr-extra__avatar">{{ $itemInitial }}</span>
                            <span class="pds-hr-extra__name">{{ $itemName }}</span>
                        </span>
                        <span class="pds-hr-extra__col-about">{{ $item->description }}</span>
                        <span class="pds-hr-extra__col-fine">
                            <span class="pds-hr-extra__amount">{{ number_format($item->amount) }}</span>
                        </span>
                        @if($canEdit)
                            <span class="pds-hr-extra__col-action">
                                {{ html()->form('DELETE', route('hr.late-fine.item.destroy', $item->id))->attribute('data--submit', 'lateitem' . $item->id)->class('d-inline')->open() }}
                                    <a href="javascript:void(0)"
                                       class="pds-hr-extra__delete"
                                       data--submit="lateitem{{ $item->id }}"
                                       data--confirmation="true"
                                       data-title="{{ __('message.delete_form_title', ['form' => __('message.hr_late_fine_item')]) }}"
                                       data-message="{{ __('message.delete_msg') }}"
                                       title="{{ __('message.delete') }}">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                {{ html()->form()->close() }}
                            </span>
                        @endif
                    </div>
                @empty
                    <div class="pds-hr-extra__empty">
                        <span class="pds-hr-extra__empty-icon"><i class="fas fa-inbox"></i></span>
                        <strong>{{ __('message.no_record_found') }}</strong>
                        <p>{{ __('message.hr_late_fine_item_hint') }}</p>
                    </div>
                @endforelse

                @if($items->isNotEmpty())
                    <div class="pds-hr-extra__footer">
                        <span>{{ __('message.hr_extra_fine_total') }}</span>
                        <strong>{{ number_format($sum_incidents) }}</strong>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @include('hr.partials.styles')

    @push('bottom_script')
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
                        $('#extra-fine-staff').select2({
                            width: '100%',
                            placeholder: '{{ __('message.name') }} ရွေးပါ',
                            allowClear: true,
                            dropdownParent: $('.pds-hr-extra__form'),
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
                        });
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
    @endpush
</x-master-layout>
