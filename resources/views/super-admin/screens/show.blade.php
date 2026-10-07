@extends('super-admin.layout')

@php
    $screenTitle = __('message.'.($screen['title_key'] ?? 'sa_screen_dispatch'));
    $screenSub = __('message.'.($screen['subtitle_key'] ?? 'sa_screen_dispatch_sub'));
@endphp

@section('title', $screenTitle)
@section('page_title', $screenTitle)
@if(! in_array((string) ($screenKey ?? ''), ['kyo-shin', 'rider-remit', 'account-creation', 'roles-permissions', 'office-salary', 'rider-salary', 'late-fine', 'welcome-promotion', 'general-setting', 'company-contact', 'api-server-setting', 'ui-theme', 'delivery-route'], true))
@section('page_sub', $monthLabel)
@endif

@section('content')
@php
    $money = fn ($n) => is_numeric($n) ? number_format((float) $n, 0) . ' Ks' : $n;
    $primaryLink = $screen['links'][0] ?? null;
    $primaryHref = $primaryLink ? route($primaryLink['route'], $primaryLink['params'] ?? []) : '#';
    $primaryLabel = $primaryLink ? __('message.'.($primaryLink['label_key'] ?? '')) : '';
    $primaryIsExternal = $primaryLink && ! str_starts_with((string) ($primaryLink['route'] ?? ''), 'super-admin.');
    $branchCols = !empty($branchRows) ? array_keys($branchRows[0]['cols'] ?? []) : [];
@endphp

@php
    $initials = function (string $name): string {
        $parts = preg_split('/\s+/u', trim($name)) ?: [];
        $chars = [];
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            $chars[] = mb_strtoupper(mb_substr($part, 0, 1));
            if (count($chars) >= 2) {
                break;
            }
        }

        return implode('', $chars) ?: '?';
    };
@endphp
<div class="sa-module-page">
    @if(!empty($screen['links']))
        <nav class="sa-screen-bar">
            <div class="sa-screen-bar__links">
                @foreach($screen['links'] as $i => $link)
                    @php
                        $linkExternal = ! str_starts_with((string) ($link['route'] ?? ''), 'super-admin.');
                    @endphp
                    <a href="{{ route($link['route'], $link['params'] ?? []) }}"
                       class="{{ $i === 0 ? 'sa-btn sa-btn-primary sa-btn-sm' : 'sa-screen-bar__chip' }}"
                       @if($linkExternal) target="_blank" rel="noopener" @endif>
                        @if($i === 0)
                            <i class="fas {{ $linkExternal ? 'fa-external-link-alt' : 'fa-arrow-right' }}" aria-hidden="true"></i>
                        @endif
                        {{ $i === 0 ? __('message.sa_open').' ' : '' }}{{ __('message.'.($link['label_key'] ?? '')) }}
                    </a>
                @endforeach
            </div>
        </nav>
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

            <div class="sa-delivery-route-body pds-route-locations-page">
                @if($tab === 'from_to')
                    @include('super-admin.screens.partials.delivery-route-from-to-board')
                @elseif($tab === 'city')
                    @include('super-admin.screens.partials.delivery-route-cities-board')
                @else
                    @include('super-admin.screens.partials.delivery-route-townships-board')
                @endif
            </div>
        </section>
    @endif

    @if(($screenKey ?? '') === 'rider-remit')
        <div class="sa-rider-remit-page">
            <section class="sa-module-panel sa-rider-remit-default">
                <header class="sa-module-panel__head">
                    <h3>{{ __('message.sa_fuel_default_title') }}</h3>
                </header>
                <form method="POST" action="{{ route('super-admin.rider-remit.default-fuel') }}" class="sa-rider-remit-default__form" id="saFuelDefaultForm">
                    @csrf
                    <label class="sa-rider-remit-default__field" for="sa_default_fuel">
                        <span>{{ __('message.sa_fuel_default_label') }}</span>
                        <input type="number"
                               id="sa_default_fuel"
                               name="fuel_amount"
                               min="0"
                               step="1"
                               value="{{ (int) ($defaultFuel ?? 10000) }}"
                               required
                               inputmode="numeric"
                               class="sa-no-spin">
                    </label>
                    <label class="sa-rider-remit-default__field" for="sa_fuel_min_ways">
                        <span>{{ __('message.sa_fuel_min_ways_label') }}</span>
                        <input type="number"
                               id="sa_fuel_min_ways"
                               name="fuel_min_ways"
                               min="1"
                               max="1000"
                               step="1"
                               value="{{ (int) ($fuelMinWays ?? 1) }}"
                               required
                               inputmode="numeric"
                               class="sa-no-spin">
                    </label>
                    <button type="submit" class="sa-module-hero__btn sa-fuel-default-form__btn">
                        {{ __('message.save') }}
                    </button>
                </form>
                <p class="sa-rider-remit-default__hint">{{ __('message.sa_fuel_min_ways_hint') }}</p>
                @error('fuel_amount')
                    <p class="sa-fuel-default-panel__err">{{ $message }}</p>
                @enderror
                @error('fuel_min_ways')
                    <p class="sa-fuel-default-panel__err">{{ $message }}</p>
                @enderror
            </section>

            <section class="sa-module-panel sa-rider-remit-list">
                <header class="sa-module-panel__head">
                    <h3>{{ __('message.sa_rider_fuel_title') }}</h3>
                    <span>
                        {{ collect($riderFuelGroups ?? [])->sum(fn ($g) => count($g['rows'] ?? [])) }}
                        {{ __('message.hr_people') }}
                    </span>
                </header>

                @forelse(($riderFuelGroups ?? []) as $group)
                    <div class="sa-rider-fuel-group">
                        <div class="sa-rider-fuel-group__head">
                            <h4>{{ $group['title'] }}</h4>
                            <span>{{ count($group['rows'] ?? []) }} {{ __('message.hr_people') }}</span>
                        </div>
                        <div class="sa-module-table-wrap">
                            <table class="sa-module-table sa-late-fine-staff-table sa-rider-remit-table">
                                <thead>
                                    <tr>
                                        <th class="sa-rider-remit-table__num">#</th>
                                        <th>{{ __('message.name') }}</th>
                                        <th>{{ __('message.rider_remit_fuel') }}</th>
                                        <th>{{ __('message.sa_fuel_min_ways_col') }}</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse(($group['rows'] ?? collect()) as $i => $member)
                                        @php $fuelFormId = 'sa-rider-fuel-'.$member->id; @endphp
                                        <tr data-rider-id="{{ $member->id }}">
                                            <td class="sa-rider-remit-table__num">{{ $i + 1 }}</td>
                                            <td>
                                                <strong>{{ $member->name }}</strong>
                                                @if(!empty($member->is_hub))
                                                    <span class="sa-rider-fuel-hub-tag">{{ __('message.sa_rider_fuel_hub_tag') }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <form method="POST"
                                                      action="{{ route('super-admin.rider-remit.rider.fuel', $member->id) }}"
                                                      class="sa-late-fine-allowance-form sa-rider-fuel-form"
                                                      id="{{ $fuelFormId }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="number"
                                                           name="fuel_amount"
                                                           min="0"
                                                           step="1"
                                                           value="{{ (int) ($member->fuel_amount ?: 0) }}"
                                                           required
                                                           inputmode="numeric"
                                                           class="sa-late-fine-allowance-input sa-no-spin">
                                                </form>
                                            </td>
                                            <td>
                                                <input type="number"
                                                       form="{{ $fuelFormId }}"
                                                       name="fuel_min_ways"
                                                       min="1"
                                                       max="1000"
                                                       step="1"
                                                       value="{{ (int) ($member->fuel_min_ways ?? 1) }}"
                                                       required
                                                       inputmode="numeric"
                                                       class="sa-late-fine-allowance-input sa-no-spin"
                                                       aria-label="{{ __('message.sa_fuel_min_ways_col') }}">
                                            </td>
                                            <td>
                                                <button type="submit" form="{{ $fuelFormId }}" class="sa-module-hero__btn sa-late-fine-allowance-btn">
                                                    {{ __('message.save') }}
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5">{{ __('message.hr_no_rider_accounts_hint') }}</td>
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
        </div>
    @endif

    @if(($screenKey ?? '') === 'late-fine')
        <div class="sa-late-fine-page sa-hr-salary-page">
        <section class="sa-module-panel sa-late-fine-default">
            <header class="sa-module-panel__head">
                <h3>{{ __('message.sa_late_fine_defaults_title') }}</h3>
            </header>
            <form method="POST" action="{{ route('super-admin.late-fine.defaults') }}" class="sa-late-fine-default__form">
                @csrf
                <div class="sa-late-fine-default__field">
                    <label for="sa_fine_per_minute">{{ __('message.hr_fine_per_minute') }}</label>
                    <input type="number"
                           id="sa_fine_per_minute"
                           name="fine_per_minute"
                           min="0"
                           step="1"
                           value="{{ (int) ($lateFineDefaults['fine_per_minute'] ?? 100) }}"
                           required
                           inputmode="numeric"
                           class="sa-no-spin">
                </div>
                <div class="sa-late-fine-default__field">
                    <label for="sa_absent_day_rate">{{ __('message.hr_absent_day_rate') }}</label>
                    <input type="number"
                           id="sa_absent_day_rate"
                           name="absent_day_rate"
                           min="0"
                           step="1"
                           value="{{ (int) ($lateFineDefaults['absent_day_rate'] ?? 3000) }}"
                           required
                           inputmode="numeric"
                           class="sa-no-spin">
                </div>
                <button type="submit" class="sa-module-hero__btn sa-fuel-default-form__btn">
                    {{ __('message.save') }}
                </button>
            </form>
            @error('fine_per_minute')
                <p class="sa-fuel-default-panel__err">{{ $message }}</p>
            @enderror
            @error('absent_day_rate')
                <p class="sa-fuel-default-panel__err">{{ $message }}</p>
            @enderror
        </section>

        <section class="sa-module-panel sa-late-fine-staff">
            <header class="sa-module-panel__head">
                <h3>{{ __('message.sa_rider_fuel_mdy_list') }}</h3>
                <span>{{ $lateFineStaff->count() }} {{ __('message.hr_people') }}</span>
            </header>
            <div class="sa-module-table-wrap">
                <table class="sa-module-table sa-late-fine-staff-table sa-late-fine-table">
                    <thead>
                        <tr>
                            <th class="sa-late-fine-table__num">#</th>
                            <th>{{ __('message.name') }}</th>
                            <th>{{ __('message.hr_allowance_minutes') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lateFineStaff as $i => $member)
                            @php
                                $allowFormId = 'sa-late-allow-'.$member->id;
                                $lateInitial = strtoupper(substr(preg_replace('/\s+/u', '', (string) $member->name), 0, 1));
                            @endphp
                            <tr data-staff-id="{{ $member->id }}">
                                <td class="sa-late-fine-table__num">{{ $i + 1 }}</td>
                                <td>
                                    <div class="sa-office-salary-person">
                                        <span class="sa-office-salary-avatar">{{ $lateInitial }}</span>
                                        <div>
                                            <strong>{{ $member->name }}</strong>
                                            <span class="sa-late-fine-group">{{ __('message.hr_group_rider') }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <form method="POST"
                                          action="{{ route('super-admin.late-fine.staff.allowance', $member->id) }}"
                                          class="sa-late-fine-allowance-form"
                                          id="{{ $allowFormId }}">
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
                                               class="sa-late-fine-allowance-input sa-no-spin">
                                    </form>
                                </td>
                                <td>
                                    <button type="submit" form="{{ $allowFormId }}" class="sa-module-hero__btn sa-late-fine-allowance-btn">
                                        {{ __('message.save') }}
                                    </button>
                                    <span class="sa-late-fine-allowance-status" aria-live="polite"></span>
                                </td>
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

        @if(!empty($lateFineSheet))
            <section class="sa-late-fine-sheet-stack">
                <header class="sa-late-fine-sheet-stack__head">
                    <h3>{{ __('message.sa_late_fine_sheet_title') }}</h3>
                </header>
                <div class="pds-hr-page sa-late-fine-sheet-embed">
                    @include('hr.partials.styles')
                    @include('hr.partials._late-fine-sheet', $lateFineSheet)
                </div>
            </section>
        @endif
        </div>
    @endif

    @if(($screenKey ?? '') === 'rider-salary')
        <div class="sa-rider-salary-page sa-hr-salary-page">
        <section class="sa-module-panel sa-rider-salary-staff">
            <header class="sa-module-panel__head">
                <h3>{{ __('message.sa_rider_way_rate_title') }}</h3>
                <span>{{ $riderSalaryStaff->count() }} {{ __('message.hr_people') }}</span>
            </header>
            <div class="sa-module-table-wrap">
                <table class="sa-module-table sa-late-fine-staff-table sa-rider-salary-table">
                    <thead>
                        <tr>
                            <th class="sa-rider-salary-table__num">#</th>
                            <th>{{ __('message.name') }}</th>
                            <th>{{ __('message.hr_way_rate') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($riderSalaryStaff as $i => $member)
                            @php
                                $wayFormId = 'sa-rider-way-'.$member->id;
                                $riderInitial = strtoupper(substr(preg_replace('/\s+/u', '', (string) $member->name), 0, 1));
                            @endphp
                            <tr data-staff-id="{{ $member->id }}">
                                <td class="sa-rider-salary-table__num">{{ $i + 1 }}</td>
                                <td>
                                    <div class="sa-office-salary-person">
                                        <span class="sa-office-salary-avatar">{{ $riderInitial }}</span>
                                        <strong>{{ $member->name }}</strong>
                                    </div>
                                </td>
                                <td>
                                    <form method="POST"
                                          action="{{ route('super-admin.rider-salary.staff.way-rate', $member->id) }}"
                                          class="sa-late-fine-allowance-form sa-rider-way-rate-form"
                                          id="{{ $wayFormId }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="number"
                                               name="way_rate"
                                               min="0"
                                               step="1"
                                               value="{{ (int) ($member->way_rate ?: 1000) }}"
                                               required
                                               inputmode="numeric"
                                               class="sa-late-fine-allowance-input sa-no-spin">
                                    </form>
                                </td>
                                <td>
                                    <button type="submit" form="{{ $wayFormId }}" class="sa-module-hero__btn sa-late-fine-allowance-btn">
                                        {{ __('message.save') }}
                                    </button>
                                    <span class="sa-late-fine-allowance-status" aria-live="polite"></span>
                                </td>
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

        @if(!empty($riderSalarySheet))
            <section class="sa-module-panel sa-hr-salary-sheet-panel">
                <header class="sa-module-panel__head">
                    <h3>{{ __('message.sa_rider_salary_sheet_title') }}</h3>
                </header>
                <div class="pds-hr-page sa-late-fine-sheet-embed sa-salary-sheet-embed">
                    @include('hr.partials.styles')
                    @include('hr.partials._rider-salary-sheet', $riderSalarySheet)
                </div>
            </section>
        @endif
        </div>
    @endif

    @if(($screenKey ?? '') === 'office-salary')
        @php
            $officeSalaryDefault = (int) ($defaultOfficeSalary ?? 600000);
        @endphp
        <div class="sa-office-salary-page sa-hr-salary-page">
        <section class="sa-module-panel sa-office-salary-default">
            <header class="sa-module-panel__head">
                <h3>{{ __('message.sa_office_salary_default_title') }}</h3>
            </header>
            <form method="POST" action="{{ route('super-admin.office-salary.default') }}" class="sa-office-salary-default__form">
                @csrf
                <label for="sa_default_office_salary">{{ __('message.hr_monthly_salary') }}</label>
                <input type="number"
                       id="sa_default_office_salary"
                       name="monthly_salary"
                       min="0"
                       step="1"
                       value="{{ $officeSalaryDefault }}"
                       required
                       inputmode="numeric"
                       class="sa-no-spin">
                <button type="submit" class="sa-module-hero__btn sa-fuel-default-form__btn">
                    {{ __('message.save') }}
                </button>
            </form>
            @error('monthly_salary')
                <p class="sa-fuel-default-panel__err">{{ $message }}</p>
            @enderror
        </section>

        <section class="sa-module-panel sa-office-salary-staff">
            <header class="sa-module-panel__head">
                <h3>{{ __('message.sa_office_salary_title') }}</h3>
                <span>{{ $officeSalaryStaff->count() }} {{ __('message.hr_people') }}</span>
            </header>
            <div class="sa-module-table-wrap">
                <table class="sa-module-table sa-late-fine-staff-table sa-office-salary-table">
                    <thead>
                        <tr>
                            <th class="sa-office-salary-table__num">#</th>
                            <th>{{ __('message.name') }}</th>
                            <th>{{ __('message.hr_monthly_salary') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($officeSalaryStaff as $i => $member)
                            @php
                                $officeFormId = 'sa-office-salary-'.$member->id;
                                $officeInitial = strtoupper(substr(preg_replace('/\s+/u', '', (string) $member->name), 0, 1));
                            @endphp
                            <tr data-staff-id="{{ $member->id }}">
                                <td class="sa-office-salary-table__num">{{ $i + 1 }}</td>
                                <td>
                                    <div class="sa-office-salary-person">
                                        <span class="sa-office-salary-avatar">{{ $officeInitial }}</span>
                                        <strong>{{ $member->name }}</strong>
                                    </div>
                                </td>
                                <td>
                                    <form method="POST"
                                          action="{{ route('super-admin.office-salary.staff.monthly-salary', $member->id) }}"
                                          class="sa-late-fine-allowance-form sa-office-salary-form"
                                          id="{{ $officeFormId }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="number"
                                               name="monthly_salary"
                                               min="0"
                                               step="1"
                                               value="{{ (int) ($member->monthly_salary ?: $officeSalaryDefault) }}"
                                               required
                                               inputmode="numeric"
                                               class="sa-late-fine-allowance-input sa-no-spin">
                                    </form>
                                </td>
                                <td>
                                    <button type="submit" form="{{ $officeFormId }}" class="sa-module-hero__btn sa-late-fine-allowance-btn">
                                        {{ __('message.save') }}
                                    </button>
                                    <span class="sa-late-fine-allowance-status" aria-live="polite"></span>
                                </td>
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

        @if(!empty($officeSalarySheet))
            <section class="sa-module-panel sa-hr-salary-sheet-panel">
                <header class="sa-module-panel__head">
                    <h3>{{ __('message.sa_office_salary_sheet_title') }}</h3>
                </header>
                <div class="pds-hr-page sa-late-fine-sheet-embed sa-salary-sheet-embed">
                    @include('hr.partials.styles')
                    @include('hr.partials._office-salary-sheet', $officeSalarySheet)
                </div>
            </section>
        @endif
        </div>
    @endif

    @if(($screenKey ?? '') === 'kyo-shin')
        <section class="sa-module-panel sa-fuel-default-panel">
            <header class="sa-module-panel__head">
                <h3>{{ __('message.kyo_shin_set_totals') }}</h3>
                <span>{{ __('message.sa_fuel_default_badge') }}</span>
            </header>
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
                            @php $kyoFormId = 'kyo-shin-total-'.preg_replace('/[^a-zA-Z0-9_-]/', '-', (string) $row['key']); @endphp
                            <tr>
                                <td><strong>{{ $row['label'] }}</strong></td>
                                <td>
                                    <form method="POST" action="{{ route('super-admin.kyo-shin.total') }}" class="sa-kyo-total-form" id="{{ $kyoFormId }}">
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
                                    </form>
                                </td>
                                <td>
                                    {{ number_format($row['cash_on_hand']) }} Ks
                                </td>
                                <td>
                                    {{ number_format($row['returned_today']) }} Ks
                                </td>
                                <td>
                                    {{ number_format($row['os_receivable']) }} Ks
                                </td>
                                <td>
                                    <button type="submit" form="{{ $kyoFormId }}" class="sa-module-hero__btn sa-late-fine-allowance-btn">
                                        {{ __('message.save') }}
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">{{ __('message.kyo_shin_empty') }}</td>
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

    @if(($screenKey ?? '') === 'welcome-promotion' && !empty($welcomePromotion))
        @include('super-admin.screens.partials.welcome-promotion-board')
    @endif

    @if(($screenKey ?? '') === 'account-creation' && !empty($accountCreation))
        @include('super-admin.screens.partials.account-creation-board')
    @endif

    @if(($screenKey ?? '') === 'roles-permissions' && !empty($rolesPermissions))
        <section class="sa-module-panel sa-roles-panel">
            <div class="sa-roles-embed">
                @include('permission.partials._roles-board', $rolesPermissions)
            </div>
        </section>
    @endif

    @if(($screenKey ?? '') === 'general-setting' && !empty($generalSetting))
        <section class="sa-settings-page">
            @if($errors->any())
                <p class="sa-fuel-default-panel__err">{{ $errors->first() }}</p>
            @endif
            @include('setting.general-setting', $generalSetting)
        </section>
    @endif

    @if(($screenKey ?? '') === 'company-contact' && !empty($companyContact))
        <section class="sa-settings-page">
            @if($errors->any())
                <p class="sa-fuel-default-panel__err">{{ $errors->first() }}</p>
            @endif
            @include('setting.company-contact-setting', $companyContact)
        </section>
    @endif

    @if(($screenKey ?? '') === 'app-store-update' && !empty($appStoreUpdate))
        @include('super-admin.screens.partials.app-store-board')
    @endif

    @if(($screenKey ?? '') === 'api-server-setting' && !empty($apiServerSetting))
        <section class="sa-settings-page">
            @if($errors->any())
                <p class="sa-fuel-default-panel__err">{{ $errors->first() }}</p>
            @endif
            @include('setting.api-server-setting', $apiServerSetting)
        </section>
    @endif

    @if(($screenKey ?? '') === 'ui-theme' && !empty($uiTheme))
        @include('super-admin.screens.partials.ui-theme-board')
    @endif

    @if(($screenKey ?? '') === 'app-copy' && !empty($appCopy))
        @include('super-admin.screens.partials.app-copy-board')
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
        <section class="sa-home-card sa-home-card--table">
            @if(!empty($nc['missingAdmins']))
                <header>
                    <div>
                        <h2>{{ __('message.sa_network_alerts') }}</h2>
                        <p>{{ count($nc['missingAdmins']) }} {{ __('message.sa_missing') }}</p>
                    </div>
                </header>
                <div class="sa-home-table-wrap">
                    <table class="sa-home-table">
                        <tbody>
                            @foreach($nc['missingAdmins'] as $row)
                                <tr class="is-open">
                                    <td>
                                        <div class="sa-person">
                                            <span class="sa-avatar">{{ $initials((string) $row['name']) }}</span>
                                            <div>
                                                <strong>{{ $row['name'] }}</strong>
                                                <small>{{ __('message.sa_unassigned') }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="sa-table-actions">
                                        <a href="{{ route('super-admin.branch-admins.create', ['branch_id' => $row['id']]) }}" class="sa-btn sa-btn-primary sa-btn-sm">
                                            {{ __('message.sa_create_admin') }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="sa-fuel-default-panel__ok" style="margin:1rem">{{ __('message.sa_network_admins_ok') }}</p>
            @endif
        </section>

        <section class="sa-kpi">
            <article>
                <p>{{ __('message.sa_fuel_default_title') }}</p>
                <strong>{{ number_format((float) ($nc['defaultFuel'] ?? 0), 0) }} Ks</strong>
                <span><a href="{{ route('super-admin.screens.show', 'rider-remit') }}">{{ __('message.edit') }}</a></span>
            </article>
            <article>
                <p>{{ __('message.sa_office_salary_default_title') }}</p>
                <strong>{{ number_format((float) ($nc['defaultOfficeSalary'] ?? 0), 0) }} Ks</strong>
                <span><a href="{{ route('super-admin.screens.show', 'office-salary') }}">{{ __('message.edit') }}</a></span>
            </article>
            <article>
                <p>{{ __('message.sa_yangon_hubs') }}</p>
                <strong>{{ count($nc['hubs'] ?? []) }}</strong>
                <span>{{ collect($nc['hubs'] ?? [])->pluck('name')->implode(' · ') }}</span>
            </article>
            @foreach($modeLabels as $key => $label)
                <article>
                    <p>{{ $label }}</p>
                    <strong>{{ (int) ($nc['settlementCounts'][$key] ?? 0) }}</strong>
                    <span>{{ __('message.branch_settlement_mode') }}</span>
                </article>
            @endforeach
        </section>

        <section class="sa-home-card sa-home-card--table">
            <header>
                <div>
                    <h2>{{ __('message.sa_branches_title') }}</h2>
                    <p>{{ count($nc['modeRows'] ?? []) }} {{ __('message.sa_branches') }}</p>
                </div>
                <a href="{{ route('super-admin.screens.show', ['screen' => 'delivery-route', 'tab' => 'from_to']) }}" class="sa-btn sa-btn-ghost sa-btn-sm">
                    {{ __('message.sa_manage_settlement_modes') }}
                </a>
            </header>
            <div class="sa-home-table-wrap">
                <table class="sa-home-table">
                    <thead>
                        <tr>
                            <th>{{ __('message.name') }}</th>
                            <th>{{ __('message.branch_settlement_mode') }}</th>
                            <th>{{ __('message.sa_admin') }}</th>
                            <th class="sa-num">{{ __('message.sa_riders') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(($nc['modeRows'] ?? []) as $row)
                            <tr class="{{ empty($row['admin']) ? 'is-open' : '' }}">
                                <td>
                                    <div class="sa-person">
                                        <span class="sa-avatar">{{ $initials((string) $row['name']) }}</span>
                                        <strong>{{ $row['name'] }}</strong>
                                    </div>
                                </td>
                                <td><span class="sa-mode-badge sa-mode-badge--{{ $row['mode'] }}">{{ $row['label'] }}</span></td>
                                <td>
                                    @if(!empty($row['admin']))
                                        {{ $row['admin'] }}
                                    @else
                                        <span class="sa-badge sa-badge-warn">{{ __('message.sa_unassigned') }}</span>
                                    @endif
                                </td>
                                <td class="sa-num">{{ number_format((int) $row['riders']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @php
        $saHasOwnBoard = in_array((string) ($screenKey ?? ''), [
            'delivery-route', 'rider-remit', 'late-fine', 'rider-salary', 'office-salary', 'kyo-shin',
            'expense-summary', 'welcome-promotion', 'account-creation', 'roles-permissions',
            'general-setting', 'company-contact', 'app-store-update', 'api-server-setting', 'ui-theme', 'app-copy', 'network',
        ], true);
    @endphp
    @if(! $saHasOwnBoard)
    <div class="sa-screen-stack">
        @if(!empty($metrics))
        <section class="sa-kpi">
            @foreach($metrics as $m)
                <article>
                    <p>{{ $m['label'] }}</p>
                    <strong>
                        @if(!empty($m['raw']))
                            {{ $m['value'] }}
                        @elseif(!empty($m['money']))
                            {{ $money($m['value']) }}
                        @else
                            {{ number_format((float) $m['value']) }}
                        @endif
                    </strong>
                    @if(!empty($m['money']))
                        <span>{{ $monthLabel }}</span>
                    @endif
                </article>
            @endforeach
        </section>
        @endif

        @if(!empty($branchRows) && !empty($branchCols))
            <section class="sa-home-card sa-home-card--table">
                <header>
                    <div>
                        <h2>{{ __('message.sa_by_branch') }}</h2>
                        <p>{{ count($branchRows) }} {{ __('message.sa_branches') }}</p>
                    </div>
                </header>
                <div class="sa-home-table-wrap">
                    <table class="sa-home-table">
                        <thead>
                            <tr>
                                <th>{{ __('message.branch') }}</th>
                                @foreach($branchCols as $col)
                                    <th class="sa-num">{{ $col }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($branchRows as $row)
                                <tr>
                                    <td>
                                        <div class="sa-person">
                                            <span class="sa-avatar">{{ $initials((string) $row['name']) }}</span>
                                            <strong>{{ $row['name'] }}</strong>
                                        </div>
                                    </td>
                                    @foreach($branchCols as $col)
                                        <td class="sa-num">{{ $row['cols'][$col] ?? '—' }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
    @endif
</div>
@endsection

@if(($screenKey ?? '') === 'late-fine' || ($screenKey ?? '') === 'rider-salary' || ($screenKey ?? '') === 'office-salary' || ($screenKey ?? '') === 'rider-remit' || ($screenKey ?? '') === 'account-creation' || ($screenKey ?? '') === 'roles-permissions' || ($screenKey ?? '') === 'general-setting' || ($screenKey ?? '') === 'api-server-setting' || ($screenKey ?? '') === 'company-contact')
@push('scripts')
@if(in_array(($screenKey ?? ''), ['late-fine', 'rider-salary', 'office-salary', 'roles-permissions', 'general-setting', 'api-server-setting', 'company-contact'], true))
<script src="{{ asset('frontend-website/assets/js/jquery.min.js') }}"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/css/iziToast.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js"></script>
@if(($screenKey ?? '') === 'general-setting')
<link rel="stylesheet" href="{{ asset('css/vendor/select2.min.css') }}">
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
@endif
<style>
    .sa-late-fine-sheet-embed {
        overflow: visible;
        margin-top: 0;
        padding-bottom: 0;
        min-width: 0;
        max-width: 100%;
    }
    .sa-late-fine-sheet-embed .pds-hr-toolbar { margin-top: 0; }
    .sa-late-fine-sheet-panel { overflow: visible; }
    .sa-system-settings-embed .select2-container { width: 100% !important; }
    .sa-app-store-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.25rem;
        margin-top: 0.5rem;
    }
    .sa-app-store-card__head {
        display: flex;
        gap: 0.85rem;
        align-items: flex-start;
        margin-bottom: 1rem;
    }
    .sa-app-store-card__icon {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.75rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: color-mix(in srgb, var(--site-color, #FE6F07) 16%, #fff);
        color: var(--site-color, #FE6F07);
        flex-shrink: 0;
    }
    .sa-app-store-card__head h4 {
        margin: 0 0 0.2rem;
        font-size: 1.05rem;
        font-weight: 700;
    }
    .sa-app-store-card__head p {
        margin: 0 0 0.55rem;
        color: #64748b;
        font-size: 0.9rem;
    }
</style>
<script>
(function ($) {
    $(document).on('click', '.sa-late-fine-sheet-embed [data--confirmation="true"], .sa-roles-embed [data--confirmation="true"], .sa-system-settings-embed [data--confirmation="true"]', function (e) {
        e.preventDefault();
        var formKey = $(this).attr('data--submit');
        var title = $(this).attr('data-title') || 'Confirm';
        var message = $(this).attr('data-message') || 'Are you sure?';
        if (!window.confirm(title + '\n\n' + message)) {
            return;
        }
        var $form = $('.sa-late-fine-sheet-embed form[data--submit="' + formKey + '"], .sa-roles-embed form[data--submit="' + formKey + '"]');
        if ($form.length) {
            $form.trigger('submit');
            return;
        }
        var href = $(this).attr('href');
        if (href && href !== '#') {
            window.location.href = href;
        }
    });
})(jQuery);
</script>
@endif
@if(($screenKey ?? '') === 'late-fine')
@include('hr.partials._late-fine-scripts', ['canEdit' => !empty($lateFineSheet['canEdit'])])
@elseif(($screenKey ?? '') === 'rider-salary')
@include('hr.partials._rider-salary-scripts', [
    'canEdit' => !empty($riderSalarySheet['canEdit']),
    'canEditDeposit' => !empty($riderSalarySheet['canEditDeposit']),
])
@elseif(($screenKey ?? '') === 'office-salary')
@include('hr.partials._office-salary-scripts', [
    'canEdit' => !empty($officeSalarySheet['canEdit']),
    'canEditDeposit' => !empty($officeSalarySheet['canEditDeposit']),
])
@elseif(($screenKey ?? '') === 'roles-permissions')
<script>
(function ($) {
    $(document).on('click', '.pds-roles-tab', function () {
        var roleId = $(this).data('role-id');
        var roleName = $(this).data('role-name') || '';
        $('.pds-roles-tab').removeClass('is-active');
        $(this).addClass('is-active');
        $('#currentRoleLabel').text(String(roleName).replace(/_/g, ' ').replace(/\b\w/g, function (c) {
            return c.toUpperCase();
        }));
        $('.pds-roles-chip').removeClass('is-visible');
        $('.pds-roles-chip[data-role-panel="' + roleId + '"]').addClass('is-visible');
        $('.pds-roles-delete-btn').removeClass('is-visible');
        $('.pds-roles-delete-btn[data-role-panel="' + roleId + '"]').addClass('is-visible');
    });
    var $active = $('.pds-roles-tab.is-active');
    if ($active.length) {
        $('.pds-roles-delete-btn').removeClass('is-visible');
        $('.pds-roles-delete-btn[data-role-panel="' + $active.data('role-id') + '"]').addClass('is-visible');
    }

    var $page = $('.pds-roles-page.is-embed');
    var csrf = $page.attr('data-csrf') || $('meta[name="csrf-token"]').attr('content') || '';
    var storeUrl = $page.attr('data-store-role-url') || '';
    var toggleUrl = $page.attr('data-toggle-url') || '';
    var deletePrompt = @json(__('message.delete_msg'));
    var pendingDeleteUrl = '';

    ['#pdsRoleAddModal', '#pdsRoleDeleteModal'].forEach(function (sel) {
        var el = document.querySelector(sel);
        if (el && el.parentNode !== document.body) {
            document.body.appendChild(el);
        }
    });

    function openModal(sel) {
        var el = document.querySelector(sel);
        if (!el) return;
        document.body.appendChild(el);
        el.hidden = false;
        el.classList.add('is-open');
    }
    function closeModals() {
        ['#pdsRoleAddModal', '#pdsRoleDeleteModal'].forEach(function (sel) {
            var el = document.querySelector(sel);
            if (!el) return;
            el.hidden = true;
            el.classList.remove('is-open');
        });
    }

    $(document).on('click', '.js-pds-role-add, .pds-roles-page.is-embed .pds-roles-add-btn', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var err = document.getElementById('pdsRoleAddError');
        if (err) { err.hidden = true; err.textContent = ''; }
        var form = document.getElementById('pdsRoleAddForm');
        if (form) form.reset();
        openModal('#pdsRoleAddModal');
        return false;
    });

    $(document).on('click', '.js-pds-role-delete', function (e) {
        e.preventDefault();
        e.stopPropagation();
        pendingDeleteUrl = this.getAttribute('data-delete-url') || '';
        var label = this.getAttribute('data-role-label') || '';
        var msg = document.getElementById('pdsRoleDeleteMessage');
        if (msg) msg.textContent = label ? (deletePrompt + ' ' + label) : deletePrompt;
        var err = document.getElementById('pdsRoleDeleteError');
        if (err) { err.hidden = true; err.textContent = ''; }
        openModal('#pdsRoleDeleteModal');
        return false;
    });

    $(document).on('click', '[data-close-modal]', function (e) {
        e.preventDefault();
        closeModals();
    });

    $(document).on('submit', '#pdsRoleAddForm', function (e) {
        e.preventDefault();
        if (!storeUrl) return;
        var form = this;
        var submitBtn = form.querySelector('[type="submit"]');
        if (submitBtn) submitBtn.disabled = true;
        fetch(storeUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                name: (form.querySelector('[name="name"]') || {}).value || '',
                employee_type_name: (form.querySelector('[name="employee_type_name"]') || {}).value || ''
            })
        }).then(function (res) {
            return res.json().then(function (data) {
                if (!res.ok) throw data;
                return data;
            });
        }).then(function () {
            window.location.reload();
        }).catch(function (err) {
            var msg = (err && err.errors && (err.errors.name || err.errors.employee_type_name))
                ? [].concat(err.errors.name || [], err.errors.employee_type_name || []).join(' ')
                : (err && err.message) || 'Could not save';
            var box = document.getElementById('pdsRoleAddError');
            if (box) { box.hidden = false; box.textContent = msg; }
        }).finally(function () {
            if (submitBtn) submitBtn.disabled = false;
        });
    });

    $(document).on('click', '#pdsRoleDeleteConfirm', function () {
        if (!pendingDeleteUrl) return;
        var btn = this;
        btn.disabled = true;
        fetch(pendingDeleteUrl, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf
            }
        }).then(function (res) {
            return res.json().then(function (data) {
                if (!res.ok) throw data;
                return data;
            });
        }).then(function () {
            window.location.reload();
        }).catch(function (err) {
            var box = document.getElementById('pdsRoleDeleteError');
            if (box) {
                box.hidden = false;
                box.textContent = (err && err.message) || 'Could not delete';
            }
        }).finally(function () {
            btn.disabled = false;
        });
    });

    $(document).on('change', '.pds-roles-page.is-embed .permission_check', function () {
        var input = this;
        if (!toggleUrl || input.disabled || input.dataset.saving === '1') return;
        var allowed = input.checked;
        input.dataset.saving = '1';
        fetch(toggleUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                role: input.getAttribute('data-role-name'),
                permission: input.getAttribute('data-permission-name'),
                allowed: allowed
            })
        }).then(function (res) {
            if (!res.ok) throw new Error('save failed');
        }).catch(function () {
            input.checked = !allowed;
        }).finally(function () {
            input.dataset.saving = '0';
        });
    });
})(jQuery);
</script>
@endif
@if(($screenKey ?? '') === 'account-creation')
<script>
(function () {
    var token = document.querySelector('meta[name="csrf-token"]');
    document.querySelectorAll('.js-sa-employee-work-toggle').forEach(function (input) {
        input.addEventListener('change', function () {
            if (input.dataset.saving === '1') return;
            var id = input.getAttribute('data-id');
            var workOn = input.checked ? 1 : 0;
            var previous = !workOn;
            var wrap = input.closest('.sa-work-switch');
            var label = wrap ? wrap.querySelector('.js-sa-work-label') : null;
            input.dataset.saving = '1';
            input.disabled = true;
            fetch(@json(url('/super-admin/account-creation')) + '/' + id + '/work-status', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token ? token.content : '',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ work_on: workOn })
            }).then(function (res) {
                return res.json().then(function (data) {
                    if (!res.ok) throw data;
                    return data;
                });
            }).then(function (data) {
                var on = !!data.work_on;
                input.checked = on;
                if (wrap) {
                    wrap.classList.toggle('is-on', on);
                    wrap.classList.toggle('is-off', !on);
                }
                if (label) label.textContent = data.label || (on ? 'On' : 'Off');
            }).catch(function () {
                input.checked = previous;
            }).finally(function () {
                input.dataset.saving = '0';
                input.disabled = false;
            });
        });
    });
})();
</script>
@endif
<script>
(function () {
    function bindSaStaffForms(selector, valueKey) {
        document.querySelectorAll(selector).forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var row = form.closest('tr');
                var status = row ? row.querySelector('.sa-late-fine-allowance-status') : null;
                var btn = form.querySelector('button[type="submit"]')
                    || (form.id ? document.querySelector('button[form="' + form.id + '"]') : null);
                var token = document.querySelector('meta[name="csrf-token"]');
                if (btn) btn.disabled = true;
                if (status) status.textContent = '';

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
                    if (status) status.textContent = '';
                    ['fuel_amount', 'fuel_min_ways', valueKey].filter(function (key, i, arr) {
                        return key && arr.indexOf(key) === i;
                    }).forEach(function (key) {
                        var input = form.querySelector('input[name="' + key + '"]')
                            || (form.id ? document.querySelector('input[form="' + form.id + '"][name="' + key + '"]') : null);
                        if (input && data[key] != null) input.value = data[key];
                    });
                    if (window.saShowSuccess) {
                        window.saShowSuccess(data.message || '');
                    }
                }).catch(function () {
                    if (status) status.textContent = 'Error';
                }).finally(function () {
                    if (btn) btn.disabled = false;
                });
            });
        });
    }

    bindSaStaffForms('.sa-kyo-total-form', 'total_amount');
    bindSaStaffForms('.sa-late-fine-allowance-form:not(.sa-rider-way-rate-form):not(.sa-rider-fuel-form):not(.sa-office-salary-form)', 'allowance_minutes');
    bindSaStaffForms('.sa-rider-way-rate-form', 'way_rate');
    bindSaStaffForms('.sa-office-salary-form', 'monthly_salary');
    bindSaStaffForms('.sa-rider-fuel-form', 'fuel_amount');

    document.querySelectorAll('.sa-rider-remit-page input[type="number"], .sa-hr-salary-page input[type="number"]').forEach(function (input) {
        input.addEventListener('wheel', function (e) {
            e.preventDefault();
        }, { passive: false });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
                e.preventDefault();
            }
        });
    });
})();
</script>
@endpush
@endif
