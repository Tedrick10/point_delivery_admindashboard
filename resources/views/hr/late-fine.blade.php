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

        <div class="pds-hr-panel pds-hr-panel--late mb-3">
            <div class="table-responsive">
                <table class="table pds-hr-table pds-hr-table--late mb-0" id="late-fine-table">
                    <thead>
                    <tr>
                        <th class="pds-hr-col-no">#</th>
                        <th class="pds-hr-col-name">{{ __('message.name') }}</th>
                        <th>{{ __('message.hr_late_minutes') }}</th>
                        <th>{{ __('message.hr_allowance_minutes') }}</th>
                        <th>{{ __('message.hr_fine_minutes') }}</th>
                        <th>1မိနစ်/{{ (int) ($finePerMinuteDefault ?? ($rows->first()->fine_per_minute ?? 100)) }}</th>
                        <th>{{ __('message.hr_fine_amount') }}</th>
                        <th>
                            {{ __('message.hr_absent_dates') }}
                            <span class="pds-hr-th-sub">{{ __('message.hr_absent_dates_kicker') }}</span>
                        </th>
                        <th>
                            {{ __('message.hr_absent_days') }}
                            <span class="pds-hr-th-sub">{{ __('message.hr_absent_days_kicker') }}</span>
                        </th>
                        <th>{{ __('message.hr_absent_fine') }}</th>
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
                            <td class="pds-hr-col-name">
                                <div class="pds-hr-person">
                                    <span class="pds-hr-avatar{{ $isRider ? ' is-rider' : '' }}">{{ $initial }}</span>
                                    <div>
                                        <div class="pds-hr-person__name">{{ $row->staff?->name }}</div>
                                        <span class="pds-hr-badge{{ $isRider ? ' is-rider' : '' }}">{{ $role }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <input type="number" min="0" class="pds-hr-input late-input" data-field="late_minutes" value="{{ $row->late_minutes }}" @disabled(! $canEdit)>
                            </td>
                            <td>
                                <span class="pds-hr-cell pds-hr-cell--muted" title="{{ __('message.hr_readonly_super_admin') }}">{{ (int) $row->allowance_minutes }}</span>
                            </td>
                            <td><span class="js-fine-minutes pds-hr-cell">{{ $row->fine_minutes }}</span></td>
                            <td>
                                <span class="pds-hr-cell pds-hr-cell--muted" title="{{ __('message.hr_readonly_super_admin') }}">{{ (int) $row->fine_per_minute }}</span>
                            </td>
                            <td><span class="js-late-fine-amount pds-hr-cell">{{ number_format($row->late_fine_amount) }}</span></td>
                            <td>
                                <input type="text" class="pds-hr-input pds-hr-input--wide late-input" data-field="absent_dates" value="{{ $row->absent_dates }}" placeholder="8/9/10" @disabled(! $canEdit)>
                            </td>
                            <td>
                                <input type="number" min="0" class="pds-hr-input late-input" data-field="absent_days" value="{{ $row->absent_days }}" @disabled(! $canEdit)>
                            </td>
                            <td><span class="js-absent-fine pds-hr-cell">{{ number_format($row->absent_fine_amount) }}</span></td>
                            <td><span class="js-total-fine pds-hr-cell pds-hr-cell--strong">{{ number_format($row->total_fine) }}</span></td>
                            <td><span class="js-grand-total pds-hr-cell pds-hr-cell--strong" style="color:var(--hr-orange)">{{ number_format($row->sheet_total) }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="pds-hr-empty">{{ __('message.hr_no_accounts_hint') }}</td>
                        </tr>
                    @endforelse
                    </tbody>
                    @if($rows->isNotEmpty())
                        <tfoot>
                        <tr class="pds-hr-tfoot">
                            <td class="pds-hr-col-no"></td>
                            <td class="pds-hr-col-name pds-hr-tfoot__label">{{ __('message.total') }}</td>
                            <td colspan="8"></td>
                            <td><span class="pds-hr-cell pds-hr-cell--strong">{{ number_format($sum_total_fine) }}</span></td>
                            <td><span class="pds-hr-cell pds-hr-cell--strong" style="color:var(--hr-orange)">{{ number_format($sum_grand) }}</span></td>
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
                        <h5 class="pds-hr-extra__title">{{ __('message.hr_late_fine_item') }}</h5>
                        <p class="pds-hr-extra__hint">{{ __('message.hr_late_fine_item_hint') }}</p>
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
                    <div class="pds-hr-extra__field pds-hr-extra__field--date">
                        <label><i class="fas fa-calendar-alt"></i> {{ __('message.hr_extra_fine_date') }}</label>
                        <input type="date"
                               name="fine_date"
                               class="pds-hr-extra__control"
                               value="{{ now('Asia/Yangon')->toDateString() }}"
                               required>
                    </div>
                    <div class="pds-hr-extra__field pds-hr-extra__field--grow">
                        <label><i class="fas fa-align-left"></i> {{ __('message.hr_extra_fine_about') }}</label>
                        <input type="text" name="description" class="pds-hr-extra__control" required
                               placeholder="{{ __('message.hr_extra_fine_about_placeholder') }}">
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
                    <span class="pds-hr-extra__col-date">{{ __('message.hr_extra_fine_date') }}</span>
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
                        $itemDate = $item->fine_date
                            ? $item->fine_date->timezone('Asia/Yangon')->format('d-m-Y')
                            : '—';
                    @endphp
                    <div class="pds-hr-extra__row">
                        <span class="pds-hr-extra__col-no">{{ $i + 1 }}</span>
                        <span class="pds-hr-extra__col-name">
                            <span class="pds-hr-extra__avatar">{{ $itemInitial }}</span>
                            <span class="pds-hr-extra__name">{{ $itemName }}</span>
                        </span>
                        <span class="pds-hr-extra__col-date">{{ $itemDate }}</span>
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

        <div class="pds-hr-panel pds-hr-extra pds-hr-extra--bag mb-3">
            <div class="pds-hr-extra__head">
                <div class="pds-hr-extra__title-wrap">
                    <span class="pds-hr-extra__icon"><i class="fas fa-wallet" aria-hidden="true"></i></span>
                    <div>
                        <h5 class="pds-hr-extra__title">{{ __('message.hr_bag_deduction') }}</h5>
                        <p class="pds-hr-extra__hint">{{ __('message.hr_bag_deduction_hint') }}</p>
                    </div>
                </div>
                <div class="pds-hr-extra__total-pill">
                    <span class="pds-hr-extra__total-pill-label">{{ __('message.total') }}</span>
                    <span class="pds-hr-extra__total-pill-value">{{ number_format($sum_bag_deductions) }}</span>
                </div>
            </div>

            @if($canEdit)
                <form method="POST" action="{{ route('hr.late-fine.bag.store') }}" class="pds-hr-extra__form" id="bag-deduction-form">
                    @csrf
                    <input type="hidden" name="month" value="{{ $monthValue }}">
                    <div class="pds-hr-extra__field">
                        <label><i class="fas fa-user"></i> {{ __('message.name') }}</label>
                        <select name="staff_id" id="bag-deduction-staff" class="pds-hr-extra__control pds-hr-extra__select2" required>
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
                    <div class="pds-hr-extra__field pds-hr-extra__field--date">
                        <label><i class="fas fa-calendar-alt"></i> {{ __('message.hr_extra_fine_date') }}</label>
                        <input type="date"
                               name="item_date"
                               class="pds-hr-extra__control"
                               value="{{ now('Asia/Yangon')->toDateString() }}"
                               required>
                    </div>
                    <div class="pds-hr-extra__field pds-hr-extra__field--grow">
                        <label><i class="fas fa-align-left"></i> {{ __('message.hr_extra_fine_about') }}</label>
                        <input type="text" name="description" class="pds-hr-extra__control" required
                               placeholder="{{ __('message.hr_bag_about_placeholder') }}">
                    </div>
                    <div class="pds-hr-extra__field pds-hr-extra__field--amount">
                        <label><i class="fas fa-coins"></i> {{ __('message.amount') }}</label>
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
                    <span class="pds-hr-extra__col-date">{{ __('message.hr_extra_fine_date') }}</span>
                    <span class="pds-hr-extra__col-about">{{ __('message.hr_extra_fine_about') }}</span>
                    <span class="pds-hr-extra__col-fine">{{ __('message.amount') }}</span>
                    @if($canEdit)
                        <span class="pds-hr-extra__col-action"></span>
                    @endif
                </div>

                @forelse($bagItems as $i => $bag)
                    @php
                        $bagName = $bag->staff?->name ?? ($bag->staff_code ?: '—');
                        $bagInitial = strtoupper(substr(preg_replace('/\s+/', '', (string) $bagName), 0, 1) ?: '?');
                        $bagDate = $bag->item_date
                            ? $bag->item_date->timezone('Asia/Yangon')->format('d-m-Y')
                            : '—';
                    @endphp
                    <div class="pds-hr-extra__row">
                        <span class="pds-hr-extra__col-no">{{ $i + 1 }}</span>
                        <span class="pds-hr-extra__col-name">
                            <span class="pds-hr-extra__avatar">{{ $bagInitial }}</span>
                            <span class="pds-hr-extra__name">{{ $bagName }}</span>
                        </span>
                        <span class="pds-hr-extra__col-date">{{ $bagDate }}</span>
                        <span class="pds-hr-extra__col-about">{{ $bag->description }}</span>
                        <span class="pds-hr-extra__col-fine">
                            <span class="pds-hr-extra__amount">{{ number_format($bag->amount) }}</span>
                        </span>
                        @if($canEdit)
                            <span class="pds-hr-extra__col-action">
                                {{ html()->form('DELETE', route('hr.late-fine.bag.destroy', $bag->id))->attribute('data--submit', 'bagitem' . $bag->id)->class('d-inline')->open() }}
                                    <a href="javascript:void(0)"
                                       class="pds-hr-extra__delete"
                                       data--submit="bagitem{{ $bag->id }}"
                                       data--confirmation="true"
                                       data-title="{{ __('message.delete_form_title', ['form' => __('message.hr_bag_deduction')]) }}"
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
                        <p>{{ __('message.hr_bag_deduction_hint') }}</p>
                    </div>
                @endforelse

                @if($bagItems->isNotEmpty())
                    <div class="pds-hr-extra__footer">
                        <span>{{ __('message.hr_bag_deduction_total') }}</span>
                        <strong>{{ number_format($sum_bag_deductions) }}</strong>
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
    @endpush
</x-master-layout>
