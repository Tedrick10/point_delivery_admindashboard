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
                <h3>{{ __('message.sa_rider_fuel_mdy_list') }}</h3>
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
                                <td>{{ __('message.hr_group_rider') }}</td>
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
                                <td colspan="5">{{ __('message.hr_no_rider_accounts_hint') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        @if(!empty($lateFineSheet))
            <section class="sa-module-panel sa-late-fine-sheet-panel">
                <header class="sa-module-panel__head">
                    <h3>{{ __('message.sa_late_fine_sheet_title') }}</h3>
                    <span>{{ $lateFineSheet['monthLabel'] ?? '' }}</span>
                </header>
                <p class="sa-fuel-default-panel__hint">{{ __('message.sa_late_fine_sheet_hint') }}</p>
                <div class="pds-hr-page sa-late-fine-sheet-embed">
                    @include('hr.partials.styles')
                    @include('hr.partials._late-fine-sheet', $lateFineSheet)
                </div>
            </section>
        @endif
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

        @if(!empty($riderSalarySheet))
            <section class="sa-module-panel sa-late-fine-sheet-panel">
                <header class="sa-module-panel__head">
                    <h3>{{ __('message.sa_rider_salary_sheet_title') }}</h3>
                    <span>{{ $riderSalarySheet['monthLabel'] ?? '' }}</span>
                </header>
                <p class="sa-fuel-default-panel__hint">{{ __('message.sa_salary_deposit_sheet_hint') }}</p>
                <div class="pds-hr-page sa-late-fine-sheet-embed sa-salary-sheet-embed">
                    @include('hr.partials.styles')
                    @include('hr.partials._rider-salary-sheet', $riderSalarySheet)
                </div>
            </section>
        @endif
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

        @if(!empty($officeSalarySheet))
            <section class="sa-module-panel sa-late-fine-sheet-panel">
                <header class="sa-module-panel__head">
                    <h3>{{ __('message.sa_office_salary_sheet_title') }}</h3>
                    <span>{{ $officeSalarySheet['monthLabel'] ?? '' }}</span>
                </header>
                <p class="sa-fuel-default-panel__hint">{{ __('message.sa_salary_deposit_sheet_hint') }}</p>
                <div class="pds-hr-page sa-late-fine-sheet-embed sa-salary-sheet-embed">
                    @include('hr.partials.styles')
                    @include('hr.partials._office-salary-sheet', $officeSalarySheet)
                </div>
            </section>
        @endif
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

    @if(($screenKey ?? '') === 'welcome-promotion' && !empty($welcomePromotion))
        @include('super-admin.screens.partials.welcome-promotion-board')
    @endif

    @if(($screenKey ?? '') === 'account-creation' && !empty($accountCreation))
        @include('super-admin.screens.partials.account-creation-board')
    @endif

    @if(($screenKey ?? '') === 'roles-permissions' && !empty($rolesPermissions))
        <section class="sa-module-panel sa-late-fine-sheet-panel">
            <header class="sa-module-panel__head">
                <h3>{{ __('message.sa_screen_roles_permissions') }}</h3>
                <span>{{ ($rolesPermissions['roles'] ?? collect())->count() }} {{ __('message.role') }}</span>
            </header>
            <p class="sa-fuel-default-panel__hint">{{ __('message.sa_roles_permissions_embed_hint') }}</p>
            <div class="sa-roles-embed">
                @include('permission.partials._roles-board', $rolesPermissions)
            </div>
        </section>
        <style>
            .sa-roles-embed .btn {
                display: inline-flex; align-items: center; gap: 6px;
                border-radius: 999px; padding: 0.5rem 0.9rem; font-weight: 700;
                text-decoration: none; border: 1px solid transparent; cursor: pointer;
            }
            .sa-roles-embed .btn-primary { background: #FE6F07; color: #fff; border-color: #FE6F07; }
            .sa-roles-embed .btn-outline-danger { background: #fff; color: #b91c1c; border-color: #fecaca; }
            .sa-roles-embed .pds-roles-hero { display: none; }
        </style>
    @endif

    @if(($screenKey ?? '') === 'general-setting' && !empty($generalSetting))
        <section class="sa-module-panel sa-system-settings-panel">
            <header class="sa-module-panel__head">
                <div>
                    <h3>{{ __('message.sa_screen_general_setting') }}</h3>
                    <p class="mb-0 text-muted">{{ __('message.sa_screen_general_setting_sub') }}</p>
                </div>
            </header>
            @if(session('success'))
                <p class="sa-fuel-default-panel__ok">{{ session('success') }}</p>
            @endif
            @if(session('error'))
                <p class="sa-fuel-default-panel__err">{{ session('error') }}</p>
            @endif
            @if($errors->any())
                <p class="sa-fuel-default-panel__err">{{ $errors->first() }}</p>
            @endif
            <div class="sa-system-settings-embed">
                @include('setting.general-setting', $generalSetting)
            </div>
        </section>
    @endif

    @if(($screenKey ?? '') === 'company-contact' && !empty($companyContact))
        <section class="sa-module-panel sa-system-settings-panel">
            <header class="sa-module-panel__head">
                <div>
                    <h3>{{ __('message.sa_screen_company_contact') }}</h3>
                    <p class="mb-0 text-muted">{{ __('message.sa_screen_company_contact_sub') }}</p>
                </div>
            </header>
            @if(session('success'))
                <p class="sa-fuel-default-panel__ok">{{ session('success') }}</p>
            @endif
            @if(session('error'))
                <p class="sa-fuel-default-panel__err">{{ session('error') }}</p>
            @endif
            <div class="sa-system-settings-embed">
                @include('setting.company-contact-setting', $companyContact)
            </div>
        </section>
    @endif

    @if(($screenKey ?? '') === 'app-store-update' && !empty($appStoreUpdate))
        @include('super-admin.screens.partials.app-store-board')
    @endif

    @if(($screenKey ?? '') === 'api-server-setting' && !empty($apiServerSetting))
        <section class="sa-module-panel sa-system-settings-panel">
            <header class="sa-module-panel__head">
                <div>
                    <h3>{{ __('message.sa_screen_api_server') }}</h3>
                    <p class="mb-0 text-muted">{{ __('message.sa_screen_api_server_sub') }}</p>
                </div>
            </header>
            @if(session('success'))
                <p class="sa-fuel-default-panel__ok">{{ session('success') }}</p>
            @endif
            @if(session('error'))
                <p class="sa-fuel-default-panel__err">{{ session('error') }}</p>
            @endif
            <div class="sa-system-settings-embed">
                @include('setting.api-server-setting', $apiServerSetting)
            </div>
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
        overflow-x: auto;
        margin-top: 4px;
        padding-bottom: 8px;
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
