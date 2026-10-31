<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter pds-hr-page">
        <div class="pds-hr-hero">
            <div class="pds-hr-hero__copy">
                <div class="pds-hr-hero__eyebrow">
                    <i class="fas fa-wallet" aria-hidden="true"></i>
                    <span>{{ __('message.hr_payroll') }}</span>
                </div>
                <h4 class="pds-hr-hero__title">{{ $pageTitle }}</h4>
                <p class="pds-hr-hero__subtitle">{{ __('message.hr_my_salary_hint') }}</p>
            </div>
            <div class="pds-hr-hero__stats">
                <div class="pds-hr-stat{{ ((float) ($salary['net_pay'] ?? 0)) < 0 ? ' pds-hr-stat--danger' : '' }}">
                    <span class="pds-hr-stat__value">{{ number_format((float) ($salary['net_pay'] ?? 0)) }}</span>
                    <span class="pds-hr-stat__label">{{ __('message.hr_net_pay') }}</span>
                </div>
                <div class="pds-hr-stat pds-hr-stat--soft">
                    <span class="pds-hr-stat__value">{{ $monthLabel }}</span>
                    <span class="pds-hr-stat__label">Month</span>
                </div>
            </div>
        </div>

        <div class="pds-hr-toolbar">
            <div class="pds-hr-month pds-hr-month--end">
                <a href="{{ route('hr.my-salary.index', ['month' => $prevMonth]) }}" class="pds-hr-month__btn"><i class="fas fa-chevron-left"></i></a>
                <span class="pds-hr-month__label">{{ $monthLabel }}</span>
                <a href="{{ route('hr.my-salary.index', ['month' => $nextMonth]) }}" class="pds-hr-month__btn"><i class="fas fa-chevron-right"></i></a>
            </div>
        </div>

        @if(! $salary)
            <div class="pds-hr-panel">
                <div class="pds-hr-empty">{{ __('message.hr_my_salary_not_found') }}</div>
            </div>
        @else
            <div class="pds-hr-summary">
                <div class="pds-hr-summary__card">
                    <span class="pds-hr-summary__label">{{ __('message.hr_total_salary') }}</span>
                    <span class="pds-hr-summary__value">{{ number_format($salary['total_salary']) }}</span>
                </div>
                <div class="pds-hr-summary__card pds-hr-summary__card--danger">
                    <span class="pds-hr-summary__label">{{ __('message.hr_total_deduction') }}</span>
                    <span class="pds-hr-summary__value">{{ number_format($salary['total_deduction']) }}</span>
                </div>
                <div class="pds-hr-summary__card {{ ((float) $salary['net_pay']) < 0 ? 'pds-hr-summary__card--danger' : 'pds-hr-summary__card--success' }}">
                    <span class="pds-hr-summary__label">{{ __('message.hr_net_pay') }}</span>
                    <span class="pds-hr-summary__value">{{ number_format($salary['net_pay']) }}</span>
                </div>
            </div>

            <div class="pds-hr-panel pds-hr-my-salary">
                <div class="pds-hr-my-salary__grid">
                    @if(($salary['staff_group'] ?? '') === 'rider')
                        <div class="pds-hr-my-salary__item">
                            <span>{{ __('message.hr_way_count') }}</span>
                            <strong>{{ number_format($salary['way_count']) }}</strong>
                        </div>
                        <div class="pds-hr-my-salary__item">
                            <span>{{ __('message.hr_way_rate') }}</span>
                            <strong>{{ number_format($salary['way_rate']) }}</strong>
                        </div>
                    @else
                        <div class="pds-hr-my-salary__item">
                            <span>{{ __('message.hr_monthly_salary') }}</span>
                            <strong>{{ number_format((float) $salary['basic_salary'] + (float) ($salary['point_salary_amount'] ?? 0)) }}</strong>
                        </div>
                        <div class="pds-hr-my-salary__item">
                            <span>{{ __('message.hr_day_rate') }}</span>
                            <strong>{{ number_format($salary['day_rate']) }}</strong>
                        </div>
                        <div class="pds-hr-my-salary__item">
                            <span>{{ __('message.hr_days_in_month') }}</span>
                            <strong>{{ $salary['days_in_month'] }}</strong>
                        </div>
                        <div class="pds-hr-my-salary__item">
                            <span>{{ __('message.hr_rest_days') }}</span>
                            <strong>{{ $salary['rest_days'] }}</strong>
                        </div>
                        <div class="pds-hr-my-salary__item">
                            <span>{{ __('message.hr_worked_days') }}</span>
                            <strong>{{ $salary['worked_days'] }}</strong>
                        </div>
                    @endif

                    <div class="pds-hr-my-salary__item">
                        <span>{{ __('message.hr_late_minute_amount') }}</span>
                        <strong>{{ number_format($salary['late_minute']) }}</strong>
                    </div>
                    <div class="pds-hr-my-salary__item">
                        <span>{{ __('message.hr_fine_amount') }}</span>
                        <strong class="text-danger">{{ number_format($salary['fine_amount']) }}</strong>
                    </div>
                    <div class="pds-hr-my-salary__item">
                        <span>{{ __('message.hr_bag_deduction') }}</span>
                        <strong>{{ number_format($salary['bag_deduction']) }}</strong>
                    </div>
                    <div class="pds-hr-my-salary__item">
                        <span>Deposit</span>
                        <strong>{{ number_format($salary['deposit']) }}</strong>
                    </div>
                    <button type="button" class="pds-hr-my-salary__item pds-hr-my-salary__item--point js-pds-point-open" id="pdsPointOpenBtn">
                        <span>{{ __('message.point_balance') }}</span>
                        <strong>{{ number_format((float) ($pointBalance ?? 0), 0) }}</strong>
                    </button>
                </div>
            </div>

            @if(!empty($pointRequests) && count($pointRequests))
                <section class="pds-point-reqs">
                    <header class="pds-point-reqs__head">
                        <div class="pds-point-reqs__title">
                            <i class="fas fa-wallet" aria-hidden="true"></i>
                            <span>{{ __('message.point_salary_requests') }}</span>
                        </div>
                        <b>{{ count($pointRequests) }}</b>
                    </header>
                    <div class="pds-point-reqs__list">
                        @foreach($pointRequests as $req)
                            @php
                                $st = (string) $req->status;
                                $tag = $st === 'approved' ? 'ok' : ($st === 'rejected' ? 'no' : 'wait');
                            @endphp
                            <article class="pds-point-reqs__card pds-point-reqs__card--{{ $tag }}">
                                <div class="pds-point-reqs__icon" aria-hidden="true">
                                    <i class="fas fa-coins"></i>
                                </div>
                                <div class="pds-point-reqs__body">
                                    <div class="pds-point-reqs__line">
                                        <strong>+{{ number_format((float) $req->points, 0) }}</strong>
                                        <span class="pds-point-reqs__unit">pts</span>
                                        <span class="pds-point-reqs__tag pds-point-reqs__tag--{{ $tag }}">{{ ucfirst($st) }}</span>
                                    </div>
                                    <div class="pds-point-reqs__meta">
                                        <span>{{ number_format((float) $req->amount, 0) }} MMK</span>
                                        <span>{{ optional($req->created_at)->format('d M Y · H:i') }}</span>
                                    </div>
                                    @if(filled($req->remark))
                                        <p class="pds-point-reqs__note">
                                            <i class="fas fa-quote-left" aria-hidden="true"></i>
                                            <span>{{ $req->remark }}</span>
                                        </p>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif

            <div class="pds-point-modal" id="pdsPointModal" hidden>
                <div class="pds-point-modal__backdrop js-pds-point-close" tabindex="-1"></div>
                <div class="pds-point-modal__panel" role="dialog" aria-modal="true" aria-labelledby="pdsPointModalTitle">
                    <button type="button" class="pds-point-modal__x js-pds-point-close" aria-label="Close">
                        <i class="fas fa-times" aria-hidden="true"></i>
                    </button>

                    <div class="pds-point-modal__top">
                        <div class="pds-point-modal__icon" aria-hidden="true">
                            <i class="fas fa-coins"></i>
                        </div>
                        <h5 id="pdsPointModalTitle">{{ __('message.point_request_on_salary') }}</h5>
                        <div class="pds-point-modal__bal">
                            <span>{{ __('message.point_balance') }}</span>
                            <strong>{{ number_format((float) ($pointBalance ?? 0), 0) }} <small>pts</small></strong>
                        </div>
                    </div>

                    {!! html()->form('POST', route('hr.my-salary.point-request'))->class('pds-point-modal__form')->open() !!}
                        <input type="hidden" name="month" value="{{ $monthValue }}">
                        <label class="pds-point-modal__field">
                            <span>{{ __('message.point_amount') }}</span>
                            <input type="number" name="points" id="pdsPointAmount" min="1" step="1" required inputmode="numeric" placeholder="0" value="">
                        </label>
                        <label class="pds-point-modal__field">
                            <span>{{ __('message.remark') }}</span>
                            <input type="text" name="remark" placeholder="Optional">
                        </label>
                        <button type="submit" class="pds-point-modal__submit">
                            {{ __('message.point_request_on_salary') }}
                        </button>
                    {!! html()->form()->close() !!}
                </div>
            </div>

            <div class="pds-point-modal pds-point-modal--warn" id="pdsPointEmptyModal" hidden>
                <div class="pds-point-modal__backdrop js-pds-point-empty-close" tabindex="-1"></div>
                <div class="pds-point-modal__panel" role="dialog" aria-modal="true" aria-labelledby="pdsPointEmptyTitle">
                    <button type="button" class="pds-point-modal__x js-pds-point-empty-close" aria-label="Close">
                        <i class="fas fa-times" aria-hidden="true"></i>
                    </button>
                    <div class="pds-point-modal__top pds-point-modal__top--empty">
                        <div class="pds-point-modal__icon pds-point-modal__icon--warn" aria-hidden="true">
                            <i class="fas fa-exclamation"></i>
                        </div>
                        <h5 id="pdsPointEmptyTitle">{{ __('message.point_insufficient_balance') }}</h5>
                        <p class="pds-point-modal__hint">{{ __('message.point_balance') }}: <strong>0</strong> pts</p>
                    </div>
                    <div class="pds-point-modal__form">
                        <button type="button" class="pds-point-modal__submit js-pds-point-empty-close">OK</button>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @include('hr.partials.styles')
    <script>
    (function () {
        var modal = document.getElementById('pdsPointModal');
        var emptyModal = document.getElementById('pdsPointEmptyModal');
        var openBtn = document.getElementById('pdsPointOpenBtn');
        var amount = document.getElementById('pdsPointAmount');
        var balance = {{ (float) ($pointBalance ?? 0) }};
        if (!openBtn) return;

        function openEl(el, focusAmount) {
            if (!el) return;
            el.hidden = false;
            document.body.classList.add('pds-point-modal-open');
            requestAnimationFrame(function () { el.classList.add('is-open'); });
            if (focusAmount && amount) {
                setTimeout(function () { amount.focus(); amount.select(); }, 80);
            }
        }
        function closeEl(el) {
            if (!el) return;
            el.classList.remove('is-open');
            setTimeout(function () {
                if (!el.classList.contains('is-open')) el.hidden = true;
                if ((!modal || modal.hidden) && (!emptyModal || emptyModal.hidden)) {
                    document.body.classList.remove('pds-point-modal-open');
                }
            }, 180);
        }

        openBtn.addEventListener('click', function () {
            if (balance <= 0) {
                openEl(emptyModal, false);
            } else {
                openEl(modal, true);
            }
        });

        if (modal) {
            modal.querySelectorAll('.js-pds-point-close').forEach(function (el) {
                el.addEventListener('click', function () { closeEl(modal); });
            });
        }
        if (emptyModal) {
            emptyModal.querySelectorAll('.js-pds-point-empty-close').forEach(function (el) {
                el.addEventListener('click', function () { closeEl(emptyModal); });
            });
        }
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            if (modal && modal.classList.contains('is-open')) closeEl(modal);
            if (emptyModal && emptyModal.classList.contains('is-open')) closeEl(emptyModal);
        });
        if (amount) {
            amount.addEventListener('wheel', function (e) { e.preventDefault(); }, { passive: false });
        }
    })();
    </script>
</x-master-layout>
