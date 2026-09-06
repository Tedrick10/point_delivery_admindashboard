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
            <div class="pds-hr-freeze-shell">
                <table class="table pds-hr-table pds-hr-table--late pds-hr-table--freeze mb-0" id="late-fine-table">
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
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
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
                    <span class="pds-hr-extra__total-pill-value js-extra-list-pill-total" data-list="extra-fine">{{ number_format($sum_incidents) }}</span>
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

            <div class="pds-hr-extra__toolbar">
                <div class="pds-hr-extra__filter-field pds-hr-extra__filter-field--search">
                    <label><i class="fas fa-search"></i> {{ __('message.search') }}</label>
                    <input type="search"
                           class="pds-hr-extra__control js-extra-list-search"
                           data-list="extra-fine"
                           placeholder="{{ __('message.hr_extra_list_search_placeholder') }}"
                           autocomplete="off">
                </div>
                <div class="pds-hr-extra__filter-field">
                    <label><i class="fas fa-filter"></i> {{ __('message.name') }}</label>
                    <select class="pds-hr-extra__control js-extra-list-name-filter" data-list="extra-fine">
                        <option value="">{{ __('message.hr_extra_list_filter_all_names') }}</option>
                        @foreach($items->map(fn ($item) => $item->staff?->name ?? ($item->staff_code ?: null))->filter()->unique()->sort()->values() as $filterName)
                            <option value="{{ mb_strtolower($filterName) }}">{{ $filterName }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="pds-hr-extra__filter-field">
                    <label><i class="fas fa-users"></i> {{ __('message.filter') }}</label>
                    <select class="pds-hr-extra__control js-extra-list-group-filter" data-list="extra-fine">
                        <option value="">{{ __('message.hr_extra_list_filter_all_groups') }}</option>
                        <option value="office">{{ __('message.hr_group_office') }}</option>
                        <option value="rider">{{ __('message.hr_group_rider') }}</option>
                    </select>
                </div>
                <button type="button" class="pds-hr-extra__filter-clear js-extra-list-clear" data-list="extra-fine">
                    <i class="fas fa-times"></i> {{ __('message.reset_filter') }}
                </button>
            </div>

            <div class="pds-hr-extra__list{{ $canEdit ? '' : ' is-readonly' }}" data-extra-list="extra-fine">
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
                        $itemGroup = ($item->staff?->staff_group ?? '') === 'rider' ? 'rider' : 'office';
                    @endphp
                    <div class="pds-hr-extra__row"
                         data-name="{{ mb_strtolower($itemName) }}"
                         data-group="{{ $itemGroup }}"
                         data-amount="{{ (float) $item->amount }}">
                        <span class="pds-hr-extra__col-no js-extra-row-no">{{ $i + 1 }}</span>
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

                <div class="pds-hr-extra__empty pds-hr-extra__empty--filter" hidden>
                    <span class="pds-hr-extra__empty-icon"><i class="fas fa-search"></i></span>
                    <strong>{{ __('message.hr_extra_list_no_match') }}</strong>
                    <p>{{ __('message.hr_extra_list_no_match_hint') }}</p>
                </div>

                @if($items->isNotEmpty())
                    <div class="pds-hr-extra__footer">
                        <span>{{ __('message.hr_extra_fine_total') }}</span>
                        <strong class="js-extra-list-total" data-list="extra-fine">{{ number_format($sum_incidents) }}</strong>
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
                    <span class="pds-hr-extra__total-pill-value js-extra-list-pill-total" data-list="bag">{{ number_format($sum_bag_deductions) }}</span>
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

            <div class="pds-hr-extra__toolbar">
                <div class="pds-hr-extra__filter-field pds-hr-extra__filter-field--search">
                    <label><i class="fas fa-search"></i> {{ __('message.search') }}</label>
                    <input type="search"
                           class="pds-hr-extra__control js-extra-list-search"
                           data-list="bag"
                           placeholder="{{ __('message.hr_extra_list_search_placeholder') }}"
                           autocomplete="off">
                </div>
                <div class="pds-hr-extra__filter-field">
                    <label><i class="fas fa-filter"></i> {{ __('message.name') }}</label>
                    <select class="pds-hr-extra__control js-extra-list-name-filter" data-list="bag">
                        <option value="">{{ __('message.hr_extra_list_filter_all_names') }}</option>
                        @foreach($bagItems->map(fn ($bag) => $bag->staff?->name ?? ($bag->staff_code ?: null))->filter()->unique()->sort()->values() as $filterName)
                            <option value="{{ mb_strtolower($filterName) }}">{{ $filterName }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="pds-hr-extra__filter-field">
                    <label><i class="fas fa-users"></i> {{ __('message.filter') }}</label>
                    <select class="pds-hr-extra__control js-extra-list-group-filter" data-list="bag">
                        <option value="">{{ __('message.hr_extra_list_filter_all_groups') }}</option>
                        <option value="office">{{ __('message.hr_group_office') }}</option>
                        <option value="rider">{{ __('message.hr_group_rider') }}</option>
                    </select>
                </div>
                <button type="button" class="pds-hr-extra__filter-clear js-extra-list-clear" data-list="bag">
                    <i class="fas fa-times"></i> {{ __('message.reset_filter') }}
                </button>
            </div>

            <div class="pds-hr-extra__list{{ $canEdit ? '' : ' is-readonly' }}" data-extra-list="bag">
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
                        $bagGroup = ($bag->staff?->staff_group ?? '') === 'rider' ? 'rider' : 'office';
                    @endphp
                    <div class="pds-hr-extra__row"
                         data-name="{{ mb_strtolower($bagName) }}"
                         data-group="{{ $bagGroup }}"
                         data-amount="{{ (float) $bag->amount }}">
                        <span class="pds-hr-extra__col-no js-extra-row-no">{{ $i + 1 }}</span>
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

                <div class="pds-hr-extra__empty pds-hr-extra__empty--filter" hidden>
                    <span class="pds-hr-extra__empty-icon"><i class="fas fa-search"></i></span>
                    <strong>{{ __('message.hr_extra_list_no_match') }}</strong>
                    <p>{{ __('message.hr_extra_list_no_match_hint') }}</p>
                </div>

                @if($bagItems->isNotEmpty())
                    <div class="pds-hr-extra__footer">
                        <span>{{ __('message.hr_bag_deduction_total') }}</span>
                        <strong class="js-extra-list-total" data-list="bag">{{ number_format($sum_bag_deductions) }}</strong>
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
    @endpush
</x-master-layout>
