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
    $primaryIsExternal = $primaryLink && ! str_starts_with((string) ($primaryLink['route'] ?? ''), 'super-admin.');
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
            <a href="{{ $primaryHref }}"
               class="sa-module-hero__btn"
               @if($primaryIsExternal) target="_blank" rel="noopener" @endif>
                <i class="fas {{ $primaryIsExternal ? 'fa-external-link-alt' : 'fa-arrow-right' }}" aria-hidden="true"></i>
                <span>{{ __('message.sa_open') }} {{ $primaryLabel }}</span>
            </a>
        @endif
    </header>

    @if(count($screen['links'] ?? []) > 1)
        <div class="sa-module-links">
            @foreach($screen['links'] as $link)
                @php
                    $linkExternal = ! str_starts_with((string) ($link['route'] ?? ''), 'super-admin.');
                @endphp
                <a href="{{ route($link['route'], $link['params'] ?? []) }}"
                   class="sa-module-links__item"
                   @if($linkExternal) target="_blank" rel="noopener" @endif>
                    {{ __('message.'.($link['label_key'] ?? '')) }}
                </a>
            @endforeach
        </div>
    @endif

    @if(($screenKey ?? '') === 'delivery-route' && !empty($deliveryRoute))
        @php
            $tab = $deliveryRoute['tab'];
            $branches = $deliveryRoute['branches'];
            $cities = $deliveryRoute['cities'];
            $townships = $deliveryRoute['townships'];
            $filterCityId = $deliveryRoute['filterCityId'];
            $canEdit = $deliveryRoute['canEdit'];
            $drRoutes = $deliveryRoute['drRoutes'];
        @endphp
        <section class="sa-module-panel sa-delivery-route-panel">
            <div class="sa-delivery-route-tabs" role="tablist">
                <a href="{{ route('super-admin.screens.show', ['screen' => 'delivery-route', 'tab' => 'from_to']) }}"
                   class="sa-delivery-route-tabs__item {{ $tab === 'from_to' ? 'is-active' : '' }}">
                    {{ __('message.from') }} / {{ __('message.to') }}
                    <em>{{ $branches->count() }}</em>
                </a>
                <a href="{{ route('super-admin.screens.show', ['screen' => 'delivery-route', 'tab' => 'city']) }}"
                   class="sa-delivery-route-tabs__item {{ $tab === 'city' ? 'is-active' : '' }}">
                    {{ __('message.city') }}
                    <em>{{ $cities->count() }}</em>
                </a>
                <a href="{{ route('super-admin.screens.show', ['screen' => 'delivery-route', 'tab' => 'township', 'city_id' => $filterCityId ?: null]) }}"
                   class="sa-delivery-route-tabs__item {{ $tab === 'township' ? 'is-active' : '' }}">
                    {{ __('message.township') }}
                    <em>{{ $townships->count() }}</em>
                </a>
            </div>

            @if(session('success'))
                <p class="sa-fuel-default-panel__ok">{{ session('success') }}</p>
            @endif

            <div class="sa-delivery-route-body pds-route-locations-page">
                @if($tab === 'from_to')
                    @include('setting.partials._delivery_route_from_to')
                @elseif($tab === 'city')
                    @include('setting.partials._delivery_route_cities')
                @else
                    @include('setting.partials._delivery_route_townships')
                @endif
            </div>
        </section>
        <style>
            .sa-delivery-route-tabs { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 1rem; }
            .sa-delivery-route-tabs__item {
                display: inline-flex; align-items: center; gap: 8px;
                padding: 0.55rem 0.9rem; border-radius: 999px;
                background: #f1f5f9; color: #334155; font-weight: 700; text-decoration: none;
            }
            .sa-delivery-route-tabs__item em {
                font-style: normal; background: #fff; border-radius: 999px;
                padding: 0.1rem 0.45rem; font-size: 0.75rem; color: #64748b;
            }
            .sa-delivery-route-tabs__item.is-active { background: #0f766e; color: #fff; }
            .sa-delivery-route-tabs__item.is-active em { color: #0f766e; }
            .sa-delivery-route-body .pds-route-form {
                display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end;
                background: #fff; border: 1px solid #e2e8f0; border-radius: 16px;
                padding: 14px 16px; margin-bottom: 16px;
            }
            .sa-delivery-route-body .pds-route-form__field { min-width: 180px; flex: 1; }
            .sa-delivery-route-body .pds-route-form__field label { display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 6px; }
            .sa-delivery-route-body .pds-route-form__field input,
            .sa-delivery-route-body .pds-route-form__field select {
                width: 100%; border: 1px solid #e2e8f0; border-radius: 10px; padding: 9px 12px;
            }
            .sa-delivery-route-body .pds-route-table-wrap { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; }
            .sa-delivery-route-body .pds-route-table { width: 100%; border-collapse: collapse; }
            .sa-delivery-route-body .pds-route-table th,
            .sa-delivery-route-body .pds-route-table td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; text-align: left; }
            .sa-delivery-route-body .pds-route-table th { font-size: 12px; letter-spacing: .04em; color: #64748b; background: #f8fafc; }
            .sa-delivery-route-body .pds-route-empty { padding: 36px 16px; text-align: center; color: #94a3b8; }
            .sa-delivery-route-body .pds-daily-check-search-btn,
            .sa-delivery-route-body .pds-cash-payout-btn {
                display: inline-flex; align-items: center; gap: 6px; border: 0; border-radius: 10px;
                padding: 9px 14px; font-weight: 700; cursor: pointer; text-decoration: none;
            }
            .sa-delivery-route-body .pds-daily-check-search-btn { background: #0f766e; color: #fff; }
            .sa-delivery-route-body .pds-cash-payout-btn--ok { background: #15803d; color: #fff; }
            .sa-delivery-route-body .pds-cash-payout-btn--warn {
                background: #fff; color: #c2410c; border: 1px solid #fdba74;
            }
            .sa-delivery-route-body .pds-route-inline-form { display: flex; gap: 8px; align-items: center; }
            .sa-delivery-route-body .pds-route-inline-form input[type="text"] {
                flex: 1; border: 1px solid #e2e8f0; border-radius: 10px; padding: 8px 10px;
            }
            .sa-delivery-route-body .pds-route-settlement-select {
                width: 100%; min-width: 180px; border: 1px solid #e2e8f0; border-radius: 10px;
                padding: 8px 10px; background: #fff; font-weight: 600;
            }
            .sa-delivery-route-body .pds-route-settlement-checks {
                display: flex; flex-wrap: wrap; gap: 8px; align-items: stretch;
            }
            .sa-delivery-route-body .pds-route-settlement-check {
                display: inline-flex; align-items: center; gap: 8px;
                margin: 0; padding: 8px 12px; border-radius: 12px;
                border: 1px solid #e2e8f0; background: #fff; cursor: pointer;
            }
            .sa-delivery-route-body .pds-route-settlement-check input {
                position: absolute; opacity: 0; pointer-events: none;
            }
            .sa-delivery-route-body .pds-route-settlement-check__box {
                width: 20px; height: 20px; border-radius: 6px; flex-shrink: 0;
                border: 2px solid #cbd5e1; background: #fff; color: transparent;
                display: inline-flex; align-items: center; justify-content: center; font-size: 11px;
            }
            .sa-delivery-route-body .pds-route-settlement-check__label {
                font-size: 13px; font-weight: 700; color: #334155; line-height: 1.2;
            }
            .sa-delivery-route-body .pds-route-settlement-check.is-active,
            .sa-delivery-route-body .pds-route-settlement-check:has(input:checked) {
                border-color: #0f766e; background: #ecfdf5; box-shadow: 0 0 0 1px #0f766e inset;
            }
            .sa-delivery-route-body .pds-route-settlement-check.is-active .pds-route-settlement-check__box,
            .sa-delivery-route-body .pds-route-settlement-check:has(input:checked) .pds-route-settlement-check__box {
                border-color: #0f766e; background: #0f766e; color: #fff;
            }
            .sa-delivery-route-body .pds-route-settlement-check.is-active .pds-route-settlement-check__label,
            .sa-delivery-route-body .pds-route-settlement-check:has(input:checked) .pds-route-settlement-check__label {
                color: #0f766e;
            }
            .sa-delivery-route-body .pds-route-actions { display: flex; gap: 8px; justify-content: flex-end; flex-wrap: wrap; }
        </style>
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
                <span>
                    {{ collect($riderFuelGroups ?? [])->sum(fn ($g) => count($g['rows'] ?? [])) }}
                    {{ __('message.hr_people') }}
                </span>
            </header>
            <p class="sa-fuel-default-panel__hint">{{ __('message.sa_rider_fuel_hint') }}</p>

            @forelse(($riderFuelGroups ?? []) as $group)
                <div class="sa-rider-fuel-group">
                    <div class="sa-rider-fuel-group__head">
                        <h4>{{ $group['title'] }}</h4>
                        <span>{{ count($group['rows'] ?? []) }} {{ __('message.hr_people') }}</span>
                    </div>
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
                                @forelse(($group['rows'] ?? collect()) as $i => $member)
                                    <tr data-rider-id="{{ $member->id }}">
                                        <td>{{ $i + 1 }}</td>
                                        <td>
                                            <strong>{{ $member->name }}</strong>
                                            @if(!empty($member->is_hub))
                                                <span class="sa-rider-fuel-hub-tag">{{ __('message.sa_rider_fuel_hub_tag') }}</span>
                                            @endif
                                        </td>
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
                </div>
            @empty
                <p class="sa-fuel-default-panel__hint">{{ __('message.hr_no_rider_accounts_hint') }}</p>
            @endforelse
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
        <section class="sa-module-panel sa-fuel-default-panel">
            <header class="sa-module-panel__head">
                <h3>{{ __('message.sa_office_salary_default_title') }}</h3>
                <span>{{ __('message.sa_fuel_default_badge') }}</span>
            </header>
            <p class="sa-fuel-default-panel__hint">{{ __('message.sa_office_salary_default_hint') }}</p>
            <form method="POST" action="{{ route('super-admin.office-salary.default') }}" class="sa-fuel-default-form">
                @csrf
                <label for="sa_default_office_salary">{{ __('message.hr_monthly_salary') }}</label>
                <div class="sa-fuel-default-form__row">
                    <input type="number"
                           id="sa_default_office_salary"
                           name="monthly_salary"
                           min="0"
                           step="1"
                           value="{{ $officeSalaryDefault }}"
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
            @error('monthly_salary')
                <p class="sa-fuel-default-panel__err">{{ $message }}</p>
            @enderror
        </section>

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

    @if(($screenKey ?? '') === 'kyo-shin')
        <section class="sa-module-panel sa-fuel-default-panel">
            <header class="sa-module-panel__head">
                <h3>{{ __('message.kyo_shin_set_totals') }}</h3>
                <span>{{ __('message.sa_fuel_default_badge') }}</span>
            </header>
            <p class="sa-fuel-default-panel__hint">{{ __('message.kyo_shin_set_totals_hint') }}</p>
            @if(session('success'))
                <p class="sa-fuel-default-panel__ok">{{ session('success') }}</p>
            @endif
            <div class="sa-module-table-wrap">
                <table class="sa-module-table sa-late-fine-staff-table">
                    <thead>
                        <tr>
                            <th>{{ __('message.kyo_shin_branch') }}</th>
                            <th>{{ __('message.kyo_shin_sa_amount') }}</th>
                            <th>{{ __('message.kyo_shin_cash_held') }}</th>
                            <th>{{ __('message.kyo_shin_returned_today') }}</th>
                            <th>{{ __('message.kyo_shin_os_receivable') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($kyoShinControl ?? []) as $row)
                            <tr>
                                <td><strong>{{ $row['label'] }}</strong></td>
                                <td>
                                    <form method="POST" action="{{ route('super-admin.kyo-shin.total') }}" class="sa-late-fine-allowance-form">
                                        @csrf
                                        <input type="hidden" name="scope_key" value="{{ $row['key'] }}">
                                        <input type="number"
                                               name="total_amount"
                                               min="0"
                                               step="1"
                                               value="{{ (int) $row['total'] }}"
                                               required
                                               inputmode="numeric"
                                               class="sa-late-fine-allowance-input">
                                        <button type="submit" class="sa-module-hero__btn sa-late-fine-allowance-btn">
                                            {{ __('message.save') }}
                                        </button>
                                    </form>
                                    <small style="display:block;margin-top:6px;color:#94a3b8">{{ number_format($row['thein_total'], 2) }} {{ __('message.kyo_shin_thein') }}</small>
                                </td>
                                <td>
                                    {{ number_format($row['cash_on_hand']) }} Ks
                                    <small style="display:block;color:#94a3b8">{{ number_format($row['thein_cash_on_hand'], 2) }} {{ __('message.kyo_shin_thein') }}</small>
                                </td>
                                <td>
                                    {{ number_format($row['returned_today']) }} Ks
                                    <small style="display:block;color:#94a3b8">{{ number_format($row['thein_returned_today'], 2) }} {{ __('message.kyo_shin_thein') }}</small>
                                </td>
                                <td>
                                    {{ number_format($row['os_receivable']) }} Ks
                                    <small style="display:block;color:#94a3b8">{{ number_format($row['thein_os_receivable'], 2) }} {{ __('message.kyo_shin_thein') }}</small>
                                </td>
                                <td>
                                    <a href="{{ route('order.kyo-shin', ['scope' => $row['key']]) }}" class="sa-module-links__item" target="_blank" rel="noopener">
                                        {{ __('message.sa_open') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">{{ __('message.kyo_shin_empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if(($screenKey ?? '') === 'expense-summary' && !empty($expenseSummary))
        @include('super-admin.screens.partials.expense-summary-board')
    @endif

    @if(($screenKey ?? '') === 'network' && !empty($networkControl))
        @php
            $nc = $networkControl;
            $modeLabels = [
                \App\Models\Branch::SETTLEMENT_MANUAL => __('message.branch_settlement_manual'),
                \App\Models\Branch::SETTLEMENT_MANUAL_HALF_DELI => __('message.branch_settlement_manual_half_deli'),
                \App\Models\Branch::SETTLEMENT_HALF_DELI => __('message.branch_settlement_half_deli'),
            ];
        @endphp
        <section class="sa-module-panel sa-network-alerts">
            <header class="sa-module-panel__head">
                <h3>{{ __('message.sa_network_alerts') }}</h3>
                <span>{{ count($nc['missingAdmins'] ?? []) }} {{ __('message.sa_missing') }}</span>
            </header>
            @if(!empty($nc['missingAdmins']))
                <div class="sa-network-alert-list">
                    @foreach($nc['missingAdmins'] as $row)
                        <div class="sa-network-alert-list__item">
                            <strong>{{ $row['name'] }}</strong>
                            <span>{{ __('message.sa_no_admin_assigned') }}</span>
                            <a href="{{ route('super-admin.branch-admins.create', ['branch_id' => $row['id']]) }}" class="sa-btn sa-btn-primary">
                                {{ __('message.sa_create_admin') }}
                            </a>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="sa-fuel-default-panel__ok">{{ __('message.sa_network_admins_ok') }}</p>
            @endif
        </section>

        <section class="sa-module-panel">
            <header class="sa-module-panel__head">
                <h3>{{ __('message.sa_network_defaults') }}</h3>
            </header>
            <div class="sa-network-defaults">
                <div class="sa-network-defaults__card">
                    <span>{{ __('message.sa_fuel_default_title') }}</span>
                    <strong>{{ number_format((float) ($nc['defaultFuel'] ?? 0), 0) }} Ks</strong>
                    <a href="{{ route('super-admin.screens.show', 'rider-remit') }}">{{ __('message.edit') }}</a>
                </div>
                <div class="sa-network-defaults__card">
                    <span>{{ __('message.sa_office_salary_default_title') }}</span>
                    <strong>{{ number_format((float) ($nc['defaultOfficeSalary'] ?? 0), 0) }} Ks</strong>
                    <a href="{{ route('super-admin.screens.show', 'office-salary') }}">{{ __('message.edit') }}</a>
                </div>
                <div class="sa-network-defaults__card">
                    <span>{{ __('message.sa_yangon_hubs') }}</span>
                    <strong>{{ count($nc['hubs'] ?? []) }}</strong>
                    <em>{{ collect($nc['hubs'] ?? [])->pluck('name')->implode(' · ') }}</em>
                </div>
            </div>
        </section>

        <section class="sa-module-panel">
            <header class="sa-module-panel__head">
                <h3>{{ __('message.branch_settlement_mode') }}</h3>
                <a href="{{ route('super-admin.screens.show', ['screen' => 'delivery-route', 'tab' => 'from_to']) }}" class="sa-module-panel__link">
                    {{ __('message.sa_manage_settlement_modes') }}
                </a>
            </header>
            <div class="sa-network-mode-chips">
                @foreach($modeLabels as $key => $label)
                    <div class="sa-network-mode-chips__item">
                        <strong>{{ (int) ($nc['settlementCounts'][$key] ?? 0) }}</strong>
                        <span>{{ $label }}</span>
                    </div>
                @endforeach
            </div>
            <div class="sa-module-table-wrap" style="margin-top:1rem">
                <table class="sa-module-table">
                    <thead>
                        <tr>
                            <th>{{ __('message.name') }}</th>
                            <th>{{ __('message.branch_settlement_mode') }}</th>
                            <th>{{ __('message.sa_branch_admins') }}</th>
                            <th>{{ __('message.sa_riders') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(($nc['modeRows'] ?? []) as $row)
                            <tr>
                                <td><strong>{{ $row['name'] }}</strong></td>
                                <td><span class="sa-mode-badge sa-mode-badge--{{ $row['mode'] }}">{{ $row['label'] }}</span></td>
                                <td>{{ $row['admin'] ?: __('message.sa_no_admin_assigned') }}</td>
                                <td>{{ number_format((int) $row['riders']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
        <style>
            .sa-network-alert-list { display: grid; gap: 10px; }
            .sa-network-alert-list__item {
                display: flex; flex-wrap: wrap; gap: 10px; align-items: center;
                padding: 12px 14px; border-radius: 12px; background: #fff7ed; border: 1px solid #fed7aa;
            }
            .sa-network-alert-list__item span { color: #9a3412; flex: 1; }
            .sa-network-defaults { display: grid; grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); gap: 12px; }
            .sa-network-defaults__card {
                background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 14px 16px;
                display: grid; gap: 6px;
            }
            .sa-network-defaults__card span { font-size: 12px; color: #64748b; font-weight: 700; }
            .sa-network-defaults__card strong { font-size: 1.25rem; color: #0f172a; }
            .sa-network-defaults__card a { color: #0f766e; font-weight: 700; text-decoration: none; }
            .sa-network-defaults__card em { font-style: normal; font-size: 12px; color: #64748b; }
            .sa-network-mode-chips { display: flex; flex-wrap: wrap; gap: 10px; }
            .sa-network-mode-chips__item {
                min-width: 140px; background: #ecfdf5; border: 1px solid #99f6e4; border-radius: 12px;
                padding: 12px 14px; display: grid; gap: 4px;
            }
            .sa-network-mode-chips__item strong { font-size: 1.35rem; color: #0f766e; }
            .sa-mode-badge {
                display: inline-flex; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700;
                background: #f1f5f9; color: #334155;
            }
            .sa-mode-badge--half_deli { background: #ecfdf5; color: #0f766e; }
            .sa-mode-badge--manual_half_deli { background: #eff6ff; color: #1d4ed8; }
            .sa-module-panel__link { color: #0f766e; font-weight: 700; text-decoration: none; font-size: 13px; }
        </style>
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
