@extends('super-admin.layout')

@php
    $screenTitle = __('message.'.($screen['title_key'] ?? 'sa_screen_dispatch'));
    $screenSub = __('message.'.($screen['subtitle_key'] ?? 'sa_screen_dispatch_sub'));
@endphp

@section('title', $screenTitle)
@section('page_title', $screenTitle)
@section('page_sub', $monthLabel)

@section('content')
@php
    $money = fn ($n) => is_numeric($n) ? number_format((float) $n, 0) . ' Ks' : $n;
    $primaryLink = $screen['links'][0] ?? null;
    $primaryHref = $primaryLink ? route($primaryLink['route'], $primaryLink['params'] ?? []) : '#';
    $primaryLabel = $primaryLink ? __('message.'.($primaryLink['label_key'] ?? '')) : '';
    $branchCols = !empty($branchRows) ? array_keys($branchRows[0]['cols'] ?? []) : [];
@endphp

<div class="sa-module-page">
    <a href="{{ route('super-admin.screens.hub') }}" class="sa-module-page__back">
        <i class="fas fa-arrow-left"></i> {{ __('message.sa_all_screens') }}
    </a>

    <header class="sa-module-hero">
        <div class="sa-module-hero__icon"><i class="fas {{ $screen['icon'] }}"></i></div>
        <div class="sa-module-hero__copy">
            <h2>{{ $screenTitle }}</h2>
            <p>{{ $screenSub }}</p>
        </div>
        @if($primaryLink)
            <a href="{{ $primaryHref }}" class="sa-module-hero__btn" target="_blank" rel="noopener">
                <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                <span>{{ __('message.sa_open') }} {{ $primaryLabel }}</span>
            </a>
        @endif
    </header>

    @if(count($screen['links'] ?? []) > 1)
        <div class="sa-module-links">
            @foreach($screen['links'] as $link)
                <a href="{{ route($link['route'], $link['params'] ?? []) }}" class="sa-module-links__item" target="_blank" rel="noopener">
                    {{ __('message.'.($link['label_key'] ?? '')) }}
                </a>
            @endforeach
        </div>
    @endif

    @if(($screenKey ?? '') === 'rider-remit')
        <section class="sa-module-panel sa-fuel-default-panel">
            <header class="sa-module-panel__head">
                <h3>{{ __('message.sa_fuel_default_title') }}</h3>
                <span>{{ __('message.sa_fuel_default_badge') }}</span>
            </header>
            <p class="sa-fuel-default-panel__hint">{{ __('message.sa_fuel_default_hint') }}</p>
            <form method="POST" action="{{ route('super-admin.rider-remit.default-fuel') }}" class="sa-fuel-default-form" id="saFuelDefaultForm">
                @csrf
                <label for="sa_default_fuel">{{ __('message.sa_fuel_default_label') }}</label>
                <div class="sa-fuel-default-form__row">
                    <input type="number"
                           id="sa_default_fuel"
                           name="fuel_amount"
                           min="0"
                           step="1"
                           value="{{ (int) ($defaultFuel ?? 10000) }}"
                           required
                           inputmode="numeric">
                    <button type="submit" class="sa-module-hero__btn sa-fuel-default-form__btn">
                        <i class="fas fa-save" aria-hidden="true"></i>
                        <span>{{ __('message.save') }}</span>
                    </button>
                </div>
            </form>
            @if(session('success'))
                <p class="sa-fuel-default-panel__ok">{{ session('success') }}</p>
            @endif
            @error('fuel_amount')
                <p class="sa-fuel-default-panel__err">{{ $message }}</p>
            @enderror
        </section>

        <section class="sa-module-panel sa-late-fine-staff-panel">
            <header class="sa-module-panel__head">
                <h3>{{ __('message.sa_rider_fuel_title') }}</h3>
                <span>{{ ($riderFuelStaff ?? collect())->count() }} {{ __('message.hr_people') }}</span>
            </header>
            <p class="sa-fuel-default-panel__hint">{{ __('message.sa_rider_fuel_hint') }}</p>
            <div class="sa-module-table-wrap">
                <table class="sa-module-table sa-late-fine-staff-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('message.name') }}</th>
                            <th>{{ __('message.rider_remit_fuel') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($riderFuelStaff ?? collect()) as $i => $member)
                            <tr data-rider-id="{{ $member->id }}">
                                <td>{{ $i + 1 }}</td>
                                <td><strong>{{ $member->name }}</strong></td>
                                <td>
                                    <form method="POST"
                                          action="{{ route('super-admin.rider-remit.rider.fuel', $member->id) }}"
                                          class="sa-late-fine-allowance-form sa-rider-fuel-form">
                                        @csrf
                                        @method('PUT')
                                        <input type="number"
                                               name="fuel_amount"
                                               min="0"
                                               step="1"
                                               value="{{ (int) ($member->fuel_amount ?: 0) }}"
                                               required
                                               inputmode="numeric"
                                               class="sa-late-fine-allowance-input">
                                        <button type="submit" class="sa-module-hero__btn sa-late-fine-allowance-btn">
                                            {{ __('message.save') }}
                                        </button>
                                    </form>
                                </td>
                                <td class="sa-late-fine-allowance-status" aria-live="polite"></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">{{ __('message.hr_no_rider_accounts_hint') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if(($screenKey ?? '') === 'late-fine')
        <section class="sa-module-panel sa-fuel-default-panel">
            <header class="sa-module-panel__head">
                <h3>{{ __('message.sa_late_fine_defaults_title') }}</h3>
                <span>{{ __('message.sa_late_fine_defaults_badge') }}</span>
            </header>
            <p class="sa-fuel-default-panel__hint">{{ __('message.sa_late_fine_defaults_hint') }}</p>
            <form method="POST" action="{{ route('super-admin.late-fine.defaults') }}" class="sa-late-fine-defaults-form">
                @csrf
                <div class="sa-late-fine-defaults-form__grid">
                    <div>
                        <label for="sa_fine_per_minute">{{ __('message.hr_fine_per_minute') }}</label>
                        <div class="sa-fuel-default-form__row">
                            <input type="number"
                                   id="sa_fine_per_minute"
                                   name="fine_per_minute"
                                   min="0"
                                   step="1"
                                   value="{{ (int) ($lateFineDefaults['fine_per_minute'] ?? 100) }}"
                                   required
                                   inputmode="numeric">
                        </div>
                    </div>
                    <div>
                        <label for="sa_absent_day_rate">{{ __('message.hr_absent_day_rate') }}</label>
                        <div class="sa-fuel-default-form__row">
                            <input type="number"
                                   id="sa_absent_day_rate"
                                   name="absent_day_rate"
                                   min="0"
                                   step="1"
                                   value="{{ (int) ($lateFineDefaults['absent_day_rate'] ?? 3000) }}"
                                   required
                                   inputmode="numeric">
                        </div>
                    </div>
                    <div class="sa-late-fine-defaults-form__action">
                        <button type="submit" class="sa-module-hero__btn sa-fuel-default-form__btn">
                            <i class="fas fa-save" aria-hidden="true"></i>
                            <span>{{ __('message.save') }}</span>
                        </button>
                    </div>
                </div>
            </form>
            @error('fine_per_minute')
                <p class="sa-fuel-default-panel__err">{{ $message }}</p>
            @enderror
            @error('absent_day_rate')
                <p class="sa-fuel-default-panel__err">{{ $message }}</p>
            @enderror
        </section>

        <section class="sa-module-panel sa-late-fine-staff-panel">
            <header class="sa-module-panel__head">
                <h3>{{ __('message.sa_late_fine_allowance_title') }}</h3>
                <span>{{ $lateFineStaff->count() }} {{ __('message.hr_people') }}</span>
            </header>
            <p class="sa-fuel-default-panel__hint">{{ __('message.sa_late_fine_allowance_hint') }}</p>
            <div class="sa-module-table-wrap">
                <table class="sa-module-table sa-late-fine-staff-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('message.name') }}</th>
                            <th>{{ __('message.hr_staff_group') }}</th>
                            <th>{{ __('message.hr_allowance_minutes') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lateFineStaff as $i => $member)
                            <tr data-staff-id="{{ $member->id }}">
                                <td>{{ $i + 1 }}</td>
                                <td><strong>{{ $member->name }}</strong></td>
                                <td>
                                    {{ $member->staff_group === 'rider' ? __('message.hr_group_rider') : __('message.hr_group_office') }}
                                </td>
                                <td>
                                    <form method="POST"
                                          action="{{ route('super-admin.late-fine.staff.allowance', $member->id) }}"
                                          class="sa-late-fine-allowance-form">
                                        @csrf
                                        @method('PUT')
                                        <input type="number"
                                               name="allowance_minutes"
                                               min="0"
                                               max="600"
                                               step="1"
                                               value="{{ (int) $member->allowance_minutes }}"
                                               required
                                               inputmode="numeric"
                                               class="sa-late-fine-allowance-input">
                                        <button type="submit" class="sa-module-hero__btn sa-late-fine-allowance-btn">
                                            {{ __('message.save') }}
                                        </button>
                                    </form>
                                </td>
                                <td class="sa-late-fine-allowance-status" aria-live="polite"></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">{{ __('message.hr_no_accounts_hint') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if(($screenKey ?? '') === 'rider-salary')
        <section class="sa-module-panel sa-late-fine-staff-panel">
            <header class="sa-module-panel__head">
                <h3>{{ __('message.sa_rider_way_rate_title') }}</h3>
                <span>{{ $riderSalaryStaff->count() }} {{ __('message.hr_people') }}</span>
            </header>
            <p class="sa-fuel-default-panel__hint">{{ __('message.sa_rider_way_rate_hint') }}</p>
            <div class="sa-module-table-wrap">
                <table class="sa-module-table sa-late-fine-staff-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('message.name') }}</th>
                            <th>{{ __('message.hr_way_rate') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($riderSalaryStaff as $i => $member)
                            <tr data-staff-id="{{ $member->id }}">
                                <td>{{ $i + 1 }}</td>
                                <td><strong>{{ $member->name }}</strong></td>
                                <td>
                                    <form method="POST"
                                          action="{{ route('super-admin.rider-salary.staff.way-rate', $member->id) }}"
                                          class="sa-late-fine-allowance-form sa-rider-way-rate-form">
                                        @csrf
                                        @method('PUT')
                                        <input type="number"
                                               name="way_rate"
                                               min="0"
                                               step="1"
                                               value="{{ (int) ($member->way_rate ?: 1000) }}"
                                               required
                                               inputmode="numeric"
                                               class="sa-late-fine-allowance-input">
                                        <button type="submit" class="sa-module-hero__btn sa-late-fine-allowance-btn">
                                            {{ __('message.save') }}
                                        </button>
                                    </form>
                                </td>
                                <td class="sa-late-fine-allowance-status" aria-live="polite"></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">{{ __('message.hr_no_rider_accounts_hint') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if(($screenKey ?? '') === 'office-salary')
        @php
            $officeSalaryDefault = (int) ($defaultOfficeSalary ?? 600000);
        @endphp
        <section class="sa-module-panel sa-late-fine-staff-panel">
            <header class="sa-module-panel__head">
                <h3>{{ __('message.sa_office_salary_title') }}</h3>
                <span>{{ $officeSalaryStaff->count() }} {{ __('message.hr_people') }}</span>
            </header>
            <p class="sa-fuel-default-panel__hint">{{ __('message.sa_office_salary_hint') }}</p>
            <div class="sa-module-table-wrap">
                <table class="sa-module-table sa-late-fine-staff-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('message.name') }}</th>
                            <th>{{ __('message.hr_monthly_salary') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($officeSalaryStaff as $i => $member)
                            <tr data-staff-id="{{ $member->id }}">
                                <td>{{ $i + 1 }}</td>
                                <td><strong>{{ $member->name }}</strong></td>
                                <td>
                                    <form method="POST"
                                          action="{{ route('super-admin.office-salary.staff.monthly-salary', $member->id) }}"
                                          class="sa-late-fine-allowance-form sa-office-salary-form">
                                        @csrf
                                        @method('PUT')
                                        <input type="number"
                                               name="monthly_salary"
                                               min="0"
                                               step="1"
                                               value="{{ (int) ($member->monthly_salary ?: $officeSalaryDefault) }}"
                                               required
                                               inputmode="numeric"
                                               class="sa-late-fine-allowance-input">
                                        <button type="submit" class="sa-module-hero__btn sa-late-fine-allowance-btn">
                                            {{ __('message.save') }}
                                        </button>
                                    </form>
                                </td>
                                <td class="sa-late-fine-allowance-status" aria-live="polite"></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">{{ __('message.hr_no_office_accounts_hint') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <div class="sa-module-page__grid">
        @if(!empty($metrics))
        <section class="sa-module-panel">
            <header class="sa-module-panel__head">
                <h3>{{ __('message.sa_tab_overview') }}</h3>
                <span>{{ $monthLabel }}</span>
            </header>
            <div class="sa-module-metrics">
                @foreach($metrics as $m)
                    <div class="sa-module-metric">
                        <span class="sa-module-metric__label">{{ $m['label'] }}</span>
                        <strong class="sa-module-metric__value">
                            @if(!empty($m['raw']))
                                {{ $m['value'] }}
                            @elseif(!empty($m['money']))
                                {{ $money($m['value']) }}
                            @else
                                {{ number_format((float) $m['value']) }}
                            @endif
                        </strong>
                    </div>
                @endforeach
            </div>
        </section>
        @endif

        @if(!empty($branchRows) && !empty($branchCols))
            <section class="sa-module-panel sa-module-panel--branch">
                <header class="sa-module-panel__head">
                    <h3>{{ __('message.sa_by_branch') }}</h3>
                    <span>{{ count($branchRows) }} {{ __('message.sa_branches') }}</span>
                </header>
                <div class="sa-module-table-wrap">
                    <table class="sa-module-table">
                        <thead>
                            <tr>
                                <th>{{ __('message.branch') }}</th>
                                @foreach($branchCols as $col)
                                    <th>{{ $col }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($branchRows as $row)
                                <tr>
                                    <td><strong>{{ $row['name'] }}</strong></td>
                                    @foreach($branchCols as $col)
                                        <td>{{ $row['cols'][$col] ?? '—' }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
</div>
@endsection

@if(($screenKey ?? '') === 'late-fine' || ($screenKey ?? '') === 'rider-salary' || ($screenKey ?? '') === 'office-salary' || ($screenKey ?? '') === 'rider-remit')
@push('scripts')
<script>
(function () {
    function bindSaStaffForms(selector, valueKey) {
        document.querySelectorAll(selector).forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var row = form.closest('tr');
                var status = row ? row.querySelector('.sa-late-fine-allowance-status') : null;
                var btn = form.querySelector('button[type="submit"]');
                var token = document.querySelector('meta[name="csrf-token"]');
                if (btn) btn.disabled = true;
                if (status) status.textContent = '…';

                fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token ? token.content : ''
                    },
                    body: new FormData(form)
                }).then(function (res) {
                    return res.json().then(function (data) {
                        if (!res.ok) throw data;
                        return data;
                    });
                }).then(function (data) {
                    if (status) status.textContent = data.message || 'OK';
                    var input = form.querySelector('input[name="' + valueKey + '"]');
                    if (input && data[valueKey] != null) input.value = data[valueKey];
                }).catch(function () {
                    if (status) status.textContent = 'Error';
                }).finally(function () {
                    if (btn) btn.disabled = false;
                });
            });
        });
    }

    bindSaStaffForms('.sa-late-fine-allowance-form:not(.sa-rider-way-rate-form):not(.sa-rider-fuel-form):not(.sa-office-salary-form)', 'allowance_minutes');
    bindSaStaffForms('.sa-rider-way-rate-form', 'way_rate');
    bindSaStaffForms('.sa-office-salary-form', 'monthly_salary');
    bindSaStaffForms('.sa-rider-fuel-form', 'fuel_amount');
})();
</script>
@endpush
@endif
